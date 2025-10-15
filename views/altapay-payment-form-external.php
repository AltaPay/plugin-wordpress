<?php
defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300..800&family=Roboto&display=swap"
          rel="stylesheet">
    <title><?php bloginfo( 'name' ); ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            font-family: Open Sans, Helvetica, Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .site-branding {
            text-align: center;
            padding: 30px 0 15px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .altapay-payment-form-cnt {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
        }

        .altapay-payment-form-cnt .payment-form-wrapper {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.16);
            margin: 30px 0 50px 0;
        }

        .altapay-payment-form-cnt .woocommerce-order-details {
            padding: 0;
            margin-bottom: 30px;
        }

        .woocommerce-order-details {
            color: #333;
            padding: 0;
            background: none;
            border: none;
            box-shadow: none;
            border-radius: 0;
        }

        .order_details {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .order_details li {
            color: #333;
            font-size: 14px;
            display: block;
            padding-bottom: 5px;
        }

        .order_details li:last-child {
            border-bottom: none;
        }

        .order_details li strong {
            font-weight: 600;
        }

        .order_details li.total strong {
            color: #222;
        }


        .altapay_page_main {
            width: 100%;
        }

        .altapay_content {
            text-align: left;
            margin-left: auto;
            margin-right: auto;
            background-color: white;
            border: 1px solid rgba(0, 0, 0, 0.16);
            padding: 20px 25px 25px 25px;
            box-sizing: border-box;
            border-radius: 10px;
            position: relative;
            box-shadow: rgba(50, 50, 93, 0.25) 0 2px 5px -1px;
        }

        .payment-title {
            margin: 0;
        }

        form {
            margin: 0;
        }

        .payment-headline {
            margin-block-start: 0;
        }

        .pensio_payment_form_card-number {
            position: relative;
        }

        .pensio_payment_form_card-number, .pensio_payment_form_cardholder,
        .pensio_payment_form-cvc-input {
            margin-top: 4px;
        }

        .pensio_payment_form_card-number input, .pensio_payment_form_cardholder input,
        .altapay-payment-form-cnt input#organisationNumber, .pensio_payment_form_input_cell input {
            padding: 12px 14px;
            width: 100%;
            border-radius: 3px;
            border: 1px solid rgba(0, 0, 0, 0.16);
            cursor: pointer;
            font-size: 16px;
            box-sizing: border-box;
            color: #666;
            background-color: white;
        }

        .pensio_payment_form_card-number input,
        .pensio_payment_form_cardholder input:focus,
        input[type=tel]:focus {
            background-color: white;
        }

        .pensioCreditCardInput {
            color: #666;
        }

        .pensio_payment_form_month select,
        .pensio_payment_form_year select,
        #idealIssuer,
        .altapay-payment-form-cnt select#birthdateDay,
        .altapay-payment-form-cnt select#birthdateMonth,
        .altapay-payment-form-cnt select#birthdateYear {
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, black 50%),
            linear-gradient(135deg, black 50%, transparent 50%);
            background-position: calc(100% - 20px) calc(20px + 2px),
            calc(100% - 15px) calc(20px + 2px), 100% 0;
            background-size: 5px 5px, 5px 5px, 40px 40px;
            background-repeat: no-repeat;
            cursor: pointer;
        }

        .pensio_payment_form_month select,
        .pensio_payment_form_year select,
        #idealIssuer,
        .altapay-payment-form-cnt select#birthdateDay,
        .altapay-payment-form-cnt select#birthdateMonth,
        .altapay-payment-form-cnt input#cancelPayment,
        .altapay-payment-form-cnt input#enableAccount,
        .altapay-payment-form-cnt input#acceptTerms,
        .altapay-payment-form-cnt input#phoneNumber,
        .altapay-payment-form-cnt select#birthdateYear {
            margin-top: 4px;
            padding: 12px 14px;
            width: 100%;
            border-radius: 3px;
            border: 1px solid rgba(0, 0, 0, 0.16);
            background-color: white;
            font-size: 16px;
        }

        .pensio_payment_form-cvc-input input {
            padding: 12px 14px;
            width: 100%;
            border-radius: 3px;
            border: 1px solid rgba(0, 0, 0, 0.16);
            cursor: pointer;
            font-size: 16px;
            background-color: white;
        }

        .pensio_payment_form_expiration {
            display: flex;
            width: 100%;
            gap: 0 10px;
        }

        .pensio_payment_form_month {
            width: 30%;

        }

        .pensio_payment_form_year {
            width: 30%;

        }

        .pensio_payment_form_cvc {
            width: 40%;
        }

        .pensio_payment_form-cvc-input {
            display: flex;
            position: relative;
        }

        .cvc-icon {
            width: 30px;
            position: absolute;
            top: 16px;
            right: 16px;
            align-items: center;
        }

        .credit-card-visa-icon {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            padding-right: 7px;
            padding-top: 14px;
            align-items: center;
        }

        .credit-card-mastercard-icon {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            padding-right: 50px;
            padding-top: 14px;
            align-items: center;
        }

        .credit-card-maestro-icon {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            padding-right: 90px;
            padding-top: 14px;
            align-items: center;
        }

        #creditCardTypeIcon {
            height: 40%;
            width: auto;
            position: absolute;
            display: flex;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto 1rem auto auto;
        }

        #creditCardTypeSecondIcon {
            height: 40%;
            width: auto;
            position: absolute;
            display: flex;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto 4rem auto auto;
        }

        #selectCardLabel {
            position: absolute;
            right: 0;
            bottom: 0;
            margin: 0 2rem 2px 0;
            font-size: 10px;
            opacity: 0.7;
        }

        .pensio_payment_form_cvc-info-text {
            font-size: 10px;
            line-height: normal;
        }

        .pensio_payment_form_label_cell {
            font-size: 14px;
        }

        .expiry_row {
            margin-top: 10px;
        }

        .cardnumber_row {
            margin-bottom: 20px;
        }

        .expiry_row {
            display: flex;
            width: 100%;
            gap: 0 10px;
        }

        .submit_row {
            margin-top: 20px;
        }

        input[type="submit"].AltaPaySubmitButton,
        input#submitbutton,
        #EPayment button[type="submit"],
        .checkout-v2 button#pensioCreditCardPaymentSubmitButton {
            outline: none;
            padding: 15px 16px;
            color: white;
            border-radius: 3px;
            width: 100%;
            border: none;
            cursor: pointer;
            box-shadow: rgba(0, 0, 0, 0.16) 0 1px 4px;
            font-weight: bold;
            font-size: 17px;
        }

        input[type="submit"].AltaPaySubmitButton,
        #EPayment button[type="submit"],
        .checkout-v2 button#pensioCreditCardPaymentSubmitButton {
            background-color: #31C37E !important;
        }

        input[type="submit"].AltaPaySubmitButton:hover,
        #EPayment button[type="submit"]:hover,
        .checkout-v2 button#pensioCreditCardPaymentSubmitButton:hover {
            background-color: #16b36e !important;
        }

        input[type="submit"].AltaPaySubmitButton:disabled,
        input#submitbutton,
        #EPayment button[type="submit"]:disabled,
        .checkout-v2 button#pensioCreditCardPaymentSubmitButton:disabled {
            background-color: black !important;
            opacity: 1 !important;
        }

        input[type="submit"].AltaPaySubmitButton:disabled:hover,
        #EPayment button[type="submit"]:disabled:hover {
            background-color: black !important;
            color: white;
        }

        input#showKlarnaPage {
            margin-bottom: 15px;
        }

        /*errors*/

        .pensio_required_field_indicator, #invalid_amex_cvc, #invalid_cvc, #invalid_cardholdername, #invalid_cardholdername, #invalid_expire_month, #invalid_expire_year {
            color: red;
            font-size: 12px;
            margin-top: 4px;
            line-height: normal;
        }

        .pensio_payment_form_invalid-cvc-input, .pensio_payment_form_invalid-cardholder-input {
            color: red;
        }

        .PensioCloseButton, .CustomAltaPayCloseButton {
            width: 40px;
            height: 20px;
            font-size: 18px;
            background-color: red;
            color: white;
            cursor: pointer;
            padding: 4px;
            position: absolute;
            right: 0;
            top: 0;
        }

        .PensioRadioButton {
            border: none;
            background-color: transparent;
            cursor: pointer;
        }

        div.PensioMultiformContainer form {
            display: none;
        }

        #PensioJavascriptDisabledSurchargeNotice {
            color: red;
            background-color: white;
        }

        #iDealPayment table {
            width: 100%;
        }

        #iDealPayment #pensioPaymentIdealSubmitButton {
            margin-top: 20px;
        }

        #idealIssuer select {
            color: #666;
        }

        .PensioRadioButton {
            border: none;
            background-color: transparent;
            cursor: pointer;
        }

        div.PensioMultiformContainer form {
            display: none;
        }

        #PensioJavascriptDisabledSurchargeNotice {
            color: red;
            background-color: white;
        }

        .altapay-page-wrapper .altapay-order-details {
            padding: 15px 0;
        }

        .altapay-payment-form-cnt select#birthdateDay,
        .altapay-payment-form-cnt select#birthdateMonth,
        .altapay-payment-form-cnt input#cancelPayment,
        .altapay-payment-form-cnt input#enableAccount,
        .altapay-payment-form-cnt input#acceptTerms,
        .altapay-payment-form-cnt input#phoneNumber {
            margin-bottom: 10px;
        }

        .altapay-payment-form-cnt div.PensioMultiformContainer form {
            position: relative;
            border: none;
            background-color: white;
            padding: 0;
            margin: 0;
            border-radius: 0;
            top: 0;
            width: 100%;
        }

        .altapay-payment-form-cnt input#CreditCardButton {
            left: 0px;
        }

        .altapay-payment-form-cnt input#GiftCardButton {
            left: 100px;
        }

        .altapay-payment-form-cnt div.PensioMultiformContainer .FormTypeButton {
            position: absolute;
            top: -40px;
            height: 40px;
            margin-left: 25px;
            border: 1px solid rgba(0, 0, 0, 0.16);
        }

        .altapay-payment-form-cnt div.PensioMultiformContainer {
            position: initial;
        }

        input#giftcard_account_identifier {
            background-color: white;
            border-radius: 3px;
            color: #666;
            border: 1px solid rgba(0, 0, 0, 0.16);
        }

        .altapay-payment-form-cnt #Invoice td.pensio_payment_form_label_cell {
            vertical-align: middle;
        }

        .PensioMultiformContainer input#giftcard_account_identifier {
            width: 100%;
        }

        .altapay-payment-form-cnt table.pensio_payment_form_table {
            margin-bottom: 0;
        }

        #klarna_options {
            padding-top: 20px;
            padding-bottom: 20px;
        }

        #EPayment .IbanPopup img {
            display: block;
        }

        #EPayment .pensio_payment_form_label_cell,
        #iDealPayment .pensio_payment_form_label_cell,
        #Mobile .pensio_payment_form_label_cell,
        #GiftCard .pensio_payment_form_label_cell {
            display: block;
            padding: 0.25em 0;
        }

        #EPayment .pensio_payment_form_input_cell,
        #iDealPayment .pensio_payment_form_input_cell,
        #Mobile .pensio_payment_form_input_cell,
        #GiftCard .pensio_payment_form_input_cell {
            padding: 0 0 1em;
            display: block;
        }

        #GiftCard tr:nth-child(2) td {
            padding-left: 0;
            padding-right: 0;
        }

        #iDealPayment td.pensio_payment_form_submit_cell,
        #iDealPayment .pensio_payment_form_input_cell,
        #GiftCard .pensio_payment_form_input_cell {
            padding: 0;
        }

        .altapay-page-wrapper {
            width: 100%;
            padding-top: 50px;
        }

        .checkout-v2 .pensio_payment_form_row {
            margin-bottom: 0;
        }

        .checkout-v2 .pensio_payment_form-date {
            cursor: pointer;
            display: flex;
            align-items: center;
            font-family: monospace !important;
            border: 1px solid rgba(0, 0, 0, 0.16);
            border-top: 0;
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 0px;
            height: 52px;
        }

        .checkout-v2 .separator {
            color: #a9a9ac;
        }

        .checkout-v2 .pensio_payment_form_year {
            width: 25%;
        }

        .checkout-v2 .pensio_payment_form_card-number input {
            padding: 16px 14px;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
            box-sizing: border-box;
            color: #666;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.16);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            outline: none;
            height: 51px;
            box-shadow: none;
        }

        .checkout-v2 .pensio_payment_form_card-number,
        .checkout-v2 .pensio_payment_form_cardholder,
        .checkout-v2 .pensio_payment_form-cvc-input {
            margin-top: 0 !important;
        }

        .checkout-v2 .pensio_payment_form_cardholder input {
            color: #666;
            outline: none;
            height: 51px;
            box-shadow: none;
        }

        .checkout-v2 .pensio_payment_form-cvc-input input {
            padding: 16px 14px;
            height: 52px;
            box-sizing: border-box;
            width: 100%;
            border-bottom: 1px solid rgba(0, 0, 0, 0.16) !important;
            border-right: 1px solid rgba(0, 0, 0, 0.16) !important;
            border-radius: 4px;
            border-top: 0;
            cursor: pointer;
            font-size: 16px;
            border-left: none;
            border-bottom-left-radius: 0;
            border-top-right-radius: 0;
            outline: none;
            color: #666;
            box-shadow: none;
        }

        .checkout-v2 .expire-month, .checkout-v2 #emonth {
            height: 51px;
            padding-top: 16px;
            padding-bottom: 16px;
            padding-left: 2px !important;
            margin: auto 4px auto 14px;
            font-family: monospace !important;
            width: 100%;
            border: none;
            outline: none;
            cursor: pointer;
            font-size: 16px;
            box-shadow: none !important;
            box-sizing: border-box;
            color: #666;
        }

        .checkout-v2 .expiry-year {
            padding: 16px 4px;
            height: 51px;
            width: 100%;
            border: none;
            outline: none;
            font-family: monospace !important;
            cursor: pointer;
            font-size: 16px;
            box-sizing: border-box;
            color: #666;
        }

        .checkout-v2 .pensio_payment_form_month {
            width: 20%;
            max-width: 40px;
        }

        .checkout-v2 .pensio_payment_form_cvc {
            width: 50%;
        }

        .checkout-v2 .pensio_payment_form_row.expiry_row {
            float: none;
            margin-top: 0;
            gap: 0;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .secure-payments-text {
            position: relative;
            text-align: right;
            font-size: 10px !important;
            padding-top: 5px;
            display: block;
        }

        .checkout-v2 div.payment-form-wrapper {
            padding: 30px 25px 25px 25px !important;
            display: inline-block;
            width: 100%;
        }

        .altapay-payment-form-cnt.altapay_content.checkout-v2 {
            padding: 30px 25px 25px 25px;
            width: 100%;
            display: inline-block;
        }

        .checkout-v2 .pensio_payment_form_cvc,
        .checkout-v2 .pensio_payment_form_date-container {
            width: 50%;
        }

        .site-branding img {
            max-width: 180px;
            max-height: 120px;
            height: auto;
            width: auto;
        }

        #Mobile table.pensio_payment_form_table {
            width: 100%;
        }

        div#paymentFormWaiting {
            text-align: center;
        }

        .site-title a {
            text-decoration: none;
            font-size: 32px;
            color: #333333;
            font-weight: 700;
        }

        .checkout-v2 li.surcharge, .checkout-v2 li.total {
            display: none;
        }

        @media screen and (min-width: 992px) {
            .altapay-page-wrapper {
                display: flex;
                column-gap: 30px;
                align-items: flex-start;
                padding-top: 50px;
            }

            .theme-storefront .altapay-page-wrapper {
                padding-top: 0;
            }

            .altapay-page-wrapper .altapay-payment-form-cnt, .altapay-page-wrapper .altapay-order-details {
                flex: 1;
            }

            .altapay-page-wrapper .altapay-order-details {
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .altapay-payment-form-cnt {
                flex-direction: column;
                width: 90%;
            }

            .order_details li {
                font-size: 0.95rem;
                white-space: normal;
            }
        }
    </style>
</head>
<?php

$order_id = isset( $_POST['shop_orderid'] ) ? wp_unslash( $_POST['shop_orderid'] ) : 0;
$order    = wc_get_order( $order_id );

if ( ! $order ) {
	return;
}

$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array(
	'completed',
	'processing'
) ) );

$container_class = 'checkout';
$cc_form_styling = get_option( 'altapay_cc_form_styling' );
$container_class .= ( $cc_form_styling === 'checkout_v2' ) ? ' checkout-v2' : '';

$surcharge     = 'no';
$wpml_language = $order->get_meta( 'wpml_language' );
if ( ! empty( $wpml_language ) ) {
	global $sitepress;
	// Check if the WPML plugin is active
	if ( defined( 'ICL_SITEPRESS_VERSION' ) && is_object( $sitepress ) ) {
		// Switch the language
		$sitepress->switch_lang( $wpml_language );
	}
}
$payment_method = wc_get_payment_gateway_by_order( $order );
if ( $payment_method && isset( $payment_method->settings ) && is_array( $payment_method->settings ) ) {
	$settings  = $payment_method->settings;
	$surcharge = $settings['surcharge'] ?? 'no';
}
?>
<body>
<div class="site-branding">
	<?php
	if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
		echo get_custom_logo();
	} else {
		echo '<div class="site-title"><a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . esc_html( get_bloginfo( 'name' ) ) . '</a></div>';
	}
	?>
</div>
<div class="altapay-payment-form-cnt <?php echo $container_class; ?>">
    <div class="payment-form-wrapper">
        <div class="woocommerce-order-details">
            <ul class="order_details">
                <li class="order">
                    <strong><?php esc_html_e( 'Order number:', 'woocommerce' ); ?></strong>
					<?php echo esc_html( $order->get_order_number() ); ?>
                </li>
				<?php if ( $surcharge === 'yes' ) { ?>
                    <li class="surcharge">
                        <strong><?php echo __( 'Surcharge:', 'woocommerce' ); ?></strong>
                        <span id="PensioSurcharge"></span>
                        <span class="currency-symbol"><?php echo get_woocommerce_currency_symbol(); ?></span>
                    </li>
                    <li class="total">
                        <strong><?php echo __( 'Total:', 'woocommerce' ); ?></strong>
                        <span id="PensioTotal"></span>
                        <span class="currency-symbol"><?php echo get_woocommerce_currency_symbol(); ?></span>
                    </li>
				<?php } else { ?>
                    <li class="total">
                        <strong><?php esc_html_e( 'Total:', 'woocommerce' ); ?></strong>
						<?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                    </li>
				<?php } ?>
            </ul>
        </div>
        <form id="PensioPaymentForm">
            <!-- your payment form fields -->
        </form>
    </div>
</div>
</body>
</html>
