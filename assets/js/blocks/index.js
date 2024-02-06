import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const terminal_key = '';
const terminal_name = '';

const settings = getSetting( terminal_key + '_data', {} );

const defaultLabel = __(
	terminal_name,
	'woo-gutenberg-products-block'
);

const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * Content component
 */
const Content = () => {
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
	name: terminal_key,
	label: (
		<>
			<span class='altapay-payment-method'>
				{ __( label, 'woocommerce-payments' ) }
				{settings.icon &&
					<img src={ settings.icon } alt='' />
				}
			</span>
		</>
	),
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( altapayPaymentMethod );