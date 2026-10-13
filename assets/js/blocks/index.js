import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { select } from '@wordpress/data';

const settings = getSetting( 'altapay_data', {} );

const { useEffect } = window.wp.element;

let session = '';

let googlePayToken = '';

const defaultLabel = __(
	'AltaPay',
	'woo-gutenberg-products-block'
);

const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * Content component
 */
const Content = ( props ) => {
	const store = select( storeKey );
	const cartData = store.getCartTotals();
	settings.subtotal = cartData.total_price / Math.pow(10, cartData.currency_minor_unit);
	const { eventRegistration, activePaymentMethod,  emitResponse } = props;
	const { onCheckoutSuccess } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onCheckoutSuccess( (arg) => {
			var orderId = arg.processingResponse.paymentDetails.order_id;
			if(settings.is_apple_pay === 'yes'){
				onApplePayButtonClicked( settings, false, null, orderId );
				return {
					type: emitResponse.responseTypes.SUCCESS
				};
			}

			if(settings.is_google_pay === 'yes'){
				return altapayGooglePayAuthorize( settings, googlePayToken, orderId ).then( ( res ) => {
					googlePayToken = '';

					if ( res.success === true ) {
						if ( res.data.method && res.data.method.toUpperCase() === 'POST' ) {
							altapayGooglePayRedirect( res.data );

							return {
								type: emitResponse.responseTypes.SUCCESS
							};
						}

						return {
							type: emitResponse.responseTypes.SUCCESS,
							redirectUrl: res.data.redirect
						};
					}

					return {
						type: emitResponse.responseTypes.ERROR,
						message: __( 'Payment failed. Please try again.', 'altapay' ),
						messageContext: emitResponse.noticeContexts.PAYMENTS
					};
				} );
			}

			return {
				type: emitResponse.responseTypes.SUCCESS
			};
		} );

		// Unsubscribes when this component is unmounted.
		return () => {
			unsubscribe();
		};

	}, [
		emitResponse.responseTypes.SUCCESS,
		onCheckoutSuccess,
	] );

	jQuery(".wc-block-components-checkout-place-order-button").click(function(){
		if (jQuery('#radio-control-wc-payment-method-options-' + settings.applepay_payment_method).is(':checked')) {
			onApplePayButtonClicked(settings, true, false);
		}
	});

	// The Google Pay sheet has to be opened from the click on the place order button.
	jQuery(".wc-block-components-checkout-place-order-button")
		.off('click.altapayGooglePay')
		.on('click.altapayGooglePay', function (event) {
			if (settings.is_google_pay !== 'yes' || googlePayToken) {
				return;
			}

			if (!jQuery('#radio-control-wc-payment-method-options-' + settings.googlepay_payment_method).is(':checked')) {
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			const placeOrderButton = this;

			altapayGooglePayLoadPaymentData(settings).then(function (token) {
				googlePayToken = token;
				placeOrderButton.click();
			}).catch(function () {
				googlePayToken = '';
			});
		});

	return decodeEntities( settings.description || '' );
};


/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * AltaPay payment method config object.
 */
const altapayPaymentMethod = {
	name: 'altapay_',
	label: (
		<>
			<span class='altapay-payment-method'>
				{__(label, 'woocommerce-payments')}
				<span>
					{settings.icon && Array.isArray(settings.icon) ? (
						settings.icon.map((iconUrl, index) => (
							<img key={index} src={iconUrl} alt={`Icon ${index}`}/>
						))
					) : (
						settings.icon && <img src={settings.icon} alt=''/>
					)}
				</span>
			</span>
		</>
	),
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => {
		if ( settings.is_google_pay !== 'yes' ) {
			return true;
		}

		if ( typeof altapayGooglePayIsReadyToPay !== 'function' ) {
			return false;
		}

		return altapayGooglePayIsReadyToPay( settings );
	},
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( altapayPaymentMethod );