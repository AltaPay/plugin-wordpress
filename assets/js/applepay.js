function onApplePayButtonClicked(applepay_obj) {

	if ( ! ApplePaySession) {
		return;
	}

	// Define ApplePayPaymentRequest
	const request = {
		"countryCode": applepay_obj.country,
		"currencyCode": applepay_obj.currency,
		"merchantCapabilities": [
			"supports3DS"
		],
		"supportedNetworks": applepay_obj.apple_pay_supported_networks,
		"total": {
			"label": applepay_obj.apply_pay_label,
			"type": "final",
			"amount": applepay_obj.subtotal
		}
	};

	// Create ApplePaySession
	const session = new ApplePaySession( 3, request );

	session.onvalidatemerchant = async event => {
		jQuery.post(
			applepay_obj.ajax_url,
			{
				ajax_nonce: applepay_obj.nonce,
				action: 'validate_merchant',
				validation_url: event.validationURL,
				terminal: applepay_obj.terminal
			},
			function (res) {
				if (res.success === true) {
					const merchantSession = jQuery.parseJSON( res.data );
					session.completeMerchantValidation( merchantSession );
				} else {
					console.log( jQuery.parseJSON( res.error ) );
				}
			}
		);
	};

	session.onpaymentmethodselected = event => {
		// Define ApplePayPaymentMethodUpdate based on the selected payment method.
		let total = {
			"label": applepay_obj.apply_pay_label,
			"type": "final",
			"amount": applepay_obj.subtotal
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
		jQuery.post(
			applepay_obj.ajax_url,
			{
				ajax_nonce: applepay_obj.nonce,
				action: 'card_wallet_authorize',
				provider_data: JSON.stringify( event.payment.token ),
				terminal: applepay_obj.terminal,
				order_id: applepay_obj.order_id
			},
			function (res) {
				let status;
				if (res.success === true) {
					status = ApplePaySession.STATUS_SUCCESS;
					session.completePayment( status );
				} else {
					status = ApplePaySession.STATUS_FAILURE;
					session.completePayment( status );
				}
				window.location = res.data.redirect;
			}
		);
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
