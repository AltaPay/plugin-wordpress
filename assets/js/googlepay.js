jQuery(function ($) {
	if ( typeof altapay_googlepay_obj === 'undefined' || ! altapay_googlepay_obj.googlepay_payment_method ) {
		return false;
	}

	// Keep the amount shown in the Google Pay sheet in sync with the cart total.
	$(document.body).on('updated_checkout', function (event, data) {
		if (data?.fragments?.altapay_cart_total) {
			altapay_googlepay_obj.subtotal = data.fragments.altapay_cart_total;
		}
	});

	let isGooglePayAvailable = null;

	const hideGooglePayWhenUnavailable = function () {
		if (isGooglePayAvailable !== false) {
			return;
		}

		$('.payment_method_' + altapay_googlepay_obj.googlepay_payment_method).hide();

		if ($('#payment_method_' + altapay_googlepay_obj.googlepay_payment_method).is(':checked')) {
			$('.wc_payment_method:visible').find('input.input-radio').first().prop('checked', true).trigger('change');
		}
	};

	altapayGooglePayIsReadyToPay( altapay_googlepay_obj ).then(function (isReadyToPay) {
		isGooglePayAvailable = isReadyToPay;
		hideGooglePayWhenUnavailable();
	});

	// The payment methods are rendered again every time the checkout is updated.
	$(document.body).on('updated_checkout', hideGooglePayWhenUnavailable);

	$('form.checkout').on('checkout_place_order_' + altapay_googlepay_obj.googlepay_payment_method, function (event, wc_checkout_form) {
		const $form = $('form.checkout');

		altapayGooglePayLoadPaymentData( altapay_googlepay_obj ).then(function (token) {
			altapayGooglePaySubmitCheckout( $form, wc_checkout_form, altapay_googlepay_obj, token );
		}).catch(function (err) {
			if (err?.statusCode === 'CANCELED') {
				return;
			}

			wc_checkout_form.submit_error(
				'<div class="woocommerce-error">' +
				( err?.statusMessage || wc_checkout_params.i18n_checkout_error ) +
				'</div>'
			);
		});

		return false;
	});
});

let altapay_google_payments_client = null;

/**
 * A single PaymentsClient is reused for every call.
 */
function altapayGetGooglePaymentsClient( googlepay_obj ) {
	if ( altapay_google_payments_client === null ) {
		altapay_google_payments_client = new google.payments.api.PaymentsClient({
			environment: googlepay_obj.environment
		});
	}

	return altapay_google_payments_client;
}

/**
 * The card payment method without the tokenization specification, as required by isReadyToPay.
 */
function altapayGooglePayBaseCardPaymentMethod( googlepay_obj ) {
	return {
		type: 'CARD',
		parameters: {
			allowedAuthMethods: googlepay_obj.auth_methods,
			allowedCardNetworks: googlepay_obj.supported_networks
		}
	};
}

/**
 * Whether the browser can pay with Google Pay.
 *
 * @return Promise<boolean>
 */
function altapayGooglePayIsReadyToPay( googlepay_obj ) {
	if ( typeof google === 'undefined' || typeof google.payments === 'undefined' ) {
		return Promise.resolve( false );
	}

	return altapayGetGooglePaymentsClient( googlepay_obj ).isReadyToPay({
		apiVersion: 2,
		apiVersionMinor: 0,
		allowedPaymentMethods: [ altapayGooglePayBaseCardPaymentMethod( googlepay_obj ) ]
	}).then(function (response) {
		return response.result === true;
	}).catch(function () {
		return false;
	});
}

/**
 * Open the Google Pay payment sheet and resolve with the payment token.
 *
 * @return Promise<string>
 */
function altapayGooglePayLoadPaymentData( googlepay_obj ) {
	const cardPaymentMethod = altapayGooglePayBaseCardPaymentMethod( googlepay_obj );

	cardPaymentMethod.tokenizationSpecification = {
		type: 'PAYMENT_GATEWAY',
		parameters: {
			gateway: googlepay_obj.gateway,
			gatewayMerchantId: googlepay_obj.gateway_merchant_id
		}
	};

	const merchantInfo = { merchantName: googlepay_obj.merchant_name };

	if ( googlepay_obj.merchant_id ) {
		merchantInfo.merchantId = googlepay_obj.merchant_id;
	}

	const request = {
		apiVersion: 2,
		apiVersionMinor: 0,
		allowedPaymentMethods: [ cardPaymentMethod ],
		merchantInfo: merchantInfo,
		transactionInfo: {
			countryCode: googlepay_obj.country,
			currencyCode: googlepay_obj.currency,
			totalPriceStatus: 'FINAL',
			totalPrice: parseFloat( googlepay_obj.subtotal ).toFixed( 2 )
		}
	};

	return altapayGetGooglePaymentsClient( googlepay_obj ).loadPaymentData( request ).then(function (paymentData) {
		return paymentData.paymentMethodData.tokenizationData.token;
	});
}

/**
 * Follow the redirect.
 */
function altapayGooglePayRedirect( data ) {
	if ( ! data?.redirect ) {
		return;
	}

	if ( ( data.method || 'GET' ).toUpperCase() !== 'POST' ) {
		window.location = data.redirect;

		return;
	}

	const form = document.createElement( 'form' );
	form.method = 'POST';
	form.action = data.redirect;

	jQuery.each( data.data || {}, function ( name, value ) {
		const field = document.createElement( 'input' );
		field.type  = 'hidden';
		field.name  = name;
		field.value = value;
		form.appendChild( field );
	} );

	document.body.appendChild( form );
	form.submit();
}

/**
 * Call the CardWalletAuthorize API with the Google Pay token.
 *
 * @return Promise<object>
 */
function altapayGooglePayAuthorize( googlepay_obj, token, order_id, order_key ) {
	return new Promise(function (resolve) {
		jQuery.post(
			googlepay_obj.ajax_url,
			{
				ajax_nonce: googlepay_obj.nonce,
				action: 'card_wallet_authorize',
				wallet: 'googlepay',
				provider_data: token,
				terminal: googlepay_obj.terminal,
				order_id: order_id,
				order_key: order_key,
				screen_width: window.screen.width,
				screen_height: window.screen.height,
				color_depth: window.screen.colorDepth,
				timezone: new Date().getTimezoneOffset()
			}
		).done( resolve ).fail(function () {
			resolve( { success: false, data: {} } );
		});
	});
}

/**
 * Create the order through the WooCommerce checkout and authorize it afterwards.
 */
function altapayGooglePaySubmitCheckout( $form, wc_checkout_form, googlepay_obj, token ) {
	const $ = jQuery;

	$form.addClass( 'processing' );

	wc_checkout_form.blockOnSubmit( $form );

	// Attach event to block reloading the page when the form has been submitted
	wc_checkout_form.attachUnloadEventsOnSubmit();

	// ajaxSetup is global, but we use it to ensure JSON is valid once returned.
	$.ajaxSetup( {
		dataFilter: function( raw_response, dataType ) {
			// We only want to work with JSON
			if ( 'json' !== dataType ) {
				return raw_response;
			}

			if ( wc_checkout_form.is_valid_json( raw_response ) ) {
				return raw_response;
			}

			// Attempt to fix the malformed JSON
			const maybe_valid_json = raw_response.match( /{"result.*}/ );

			if ( null === maybe_valid_json ) {
				console.log( 'Unable to fix malformed JSON' );
			} else if ( wc_checkout_form.is_valid_json( maybe_valid_json[0] ) ) {
				console.log( 'Fixed malformed JSON. Original:' );
				console.log( raw_response );
				raw_response = maybe_valid_json[0];
			} else {
				console.log( 'Unable to fix malformed JSON' );
			}

			return raw_response;
		}
	} );

	$.ajax({
		type:		'POST',
		url:		wc_checkout_params.checkout_url,
		data:		$form.serialize(),
		dataType:   'json',
		success:	function( result ) {
			// Detach the unload handler that prevents a reload / redirect
			wc_checkout_form.detachUnloadEventsOnSubmit();

			if ( 'success' !== result.result || $form.triggerHandler( 'checkout_place_order_success', [ result, wc_checkout_form ] ) === false ) {
				// Reload page
				if ( true === result.reload ) {
					window.location.reload();
					return;
				}

				// Trigger update in case we need a fresh nonce
				if ( true === result.refresh ) {
					$( document.body ).trigger( 'update_checkout' );
				}

				// Add new errors
				if ( result.messages ) {
					wc_checkout_form.submit_error( result.messages );
				} else {
					wc_checkout_form.submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' ); // eslint-disable-line max-len
				}

				return;
			}

			altapayGooglePayAuthorize( googlepay_obj, token, result.order_id, result.order_key ).then(function (res) {
				wc_checkout_form.$checkout_form.removeClass( 'processing' ).unblock();

				altapayGooglePayRedirect( res.data );
			});
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			// Detach the unload handler that prevents a reload / redirect
			wc_checkout_form.detachUnloadEventsOnSubmit();

			wc_checkout_form.submit_error(
				'<div class="woocommerce-error">' +
				( errorThrown || wc_checkout_params.i18n_checkout_error ) +
				'</div>'
			);
		}
	});
}
