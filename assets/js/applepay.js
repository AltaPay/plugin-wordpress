jQuery( document ).ready(
	function($) {
		$( 'form.checkout' ).on(
			'checkout_place_order',
			function() {
				const payment_method = $( 'form.checkout input[name="payment_method"]:checked' ).val();

				if ($.inArray( payment_method, applepay_ajax_obj.apple_pay_terminal ) > -1) {
					onApplePayButtonClicked( payment_method );
					return false;
				}
			}
		);
	}
);

function onApplePayButtonClicked(payment_method) {

	if ( ! ApplePaySession) {
		return;
	}

	// Define ApplePayPaymentRequest
	const request = {
		"countryCode": applepay_ajax_obj.country,
		"currencyCode": applepay_ajax_obj.currency,
		"merchantCapabilities": [
			"supports3DS"
		],
		"supportedNetworks": [
			"visa",
			"masterCard",
			"amex",
			"discover"
		],
		"total": {
			"label": "AltaPay - Apple Pay",
			"type": "final",
			"amount": applepay_ajax_obj.cart_totals.subtotal
		}
	};

	// Create ApplePaySession
	const session = new ApplePaySession( 3, request );

	session.onvalidatemerchant = async event => {
		jQuery.post(
			applepay_ajax_obj.ajax_url,
			{
				ajax_nonce: applepay_ajax_obj.nonce,
				action: 'validate_merchant',
				validation_url: event.validationURL,
				terminal_id: payment_method
			},
			function (res) {
				if (res.success === true) {
					const merchantSession = jQuery.parseJSON( res.data );
					session.completeMerchantValidation( merchantSession );
					jQuery( 'form.checkout' ).submit();
				} else {
					console.log( jQuery.parseJSON( res.error ) );
				}
			}
		);
	};

	session.onpaymentmethodselected = event => {
		// Define ApplePayPaymentMethodUpdate based on the selected payment method.
		let total = {
			"label": "AltaPay - Apple Pay",
			"type": "final",
			"amount": applepay_ajax_obj.cart_totals.subtotal
		}

		const update = { "newTotal": total };
		session.completePaymentMethodSelection( update );
	};

	session.onshippingmethodselected = event => {
		// Define ApplePayShippingMethodUpdate based on the selected shipping method.
		// No updates or errors are needed, pass an empty object.
		const update = {};
		session.completeShippingMethodSelection( update );
	};

	session.onshippingcontactselected = event => {
		// Define ApplePayShippingContactUpdate based on the selected shipping contact.
		const update = {};
		session.completeShippingContactSelection( update );
	};

	session.onpaymentauthorized = event => {
		// Define ApplePayPaymentAuthorizationResult
		const result = {
			"status": ApplePaySession.STATUS_SUCCESS
		};
		session.completePayment( result );
	};

	session.oncouponcodechanged = event => {
		// Define ApplePayCouponCodeUpdate
		const newTotal           = calculateNewTotal( event.couponCode );
		const newLineItems       = calculateNewLineItems( event.couponCode );
		const newShippingMethods = calculateNewShippingMethods( event.couponCode );
		const errors             = calculateErrors( event.couponCode );

		session.completeCouponCodeChange(
			{
				newTotal: newTotal,
				newLineItems: newLineItems,
				newShippingMethods: newShippingMethods,
				errors: errors,
			}
		);
	};

	session.oncancel = event => {
		// Payment cancelled by WebKit
	};

	session.begin();
}
