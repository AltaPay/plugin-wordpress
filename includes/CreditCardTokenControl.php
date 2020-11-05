<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Respective filters for binding functionality with WordPress are added
add_filter( 'woocommerce_account_menu_items', 'savedCreditCardMenuLink', 40 );
add_action( 'init', 'savedCreditCardEndpoint' );
add_action( 'woocommerce_account_saved-credit-cards_endpoint', 'savedCreditCardEndpointContent' );
add_filter( 'woocommerce_gateway_description', 'creditCardCustomDescription', 99, 2 );
add_filter( 'woocommerce_thankyou_order_received_text', 'filterSaveCreditCardDetailsButton', 10, 2 );
add_action( 'woocommerce_checkout_process', 'setCreditCardSessionVariable' );

/**
 * Add Link (Tab) to My Account menu
 *
 * @param array $menuLinks
 *
 * @return array
 */
function savedCreditCardMenuLink( $menuLinks ) {
	$menuLinks = array_slice( $menuLinks, 0, 5, true )
				 + array( 'saved-credit-cards' => 'Saved credit card' )
				 + array_slice( $menuLinks, 5, null, true );

	return $menuLinks;

}

/**
 * Register Permalink Endpoint
 *
 * @return void
 */
function savedCreditCardEndpoint() {
	// WP_Rewrite is my Achilles' heel, so please do not ask me for detailed explanation
	add_rewrite_endpoint( 'saved-credit-cards', EP_PAGES );
	flush_rewrite_rules();

}

/**
 * Content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
 *
 * @return void
 * @throws Exception
 */
function savedCreditCardEndpointContent() {
	 global $wpdb;

	$userID  = get_current_user_id();
	$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}altapayCreditCardDetails WHERE userID='$userID'" );
	$blade   = new AltapayHelpers();
	echo _( '<h3>Saved credit card(s)</h3>' );
	echo $blade->loadBladeLibrary()->run(
		'tables.creditCard',
		array(
			'results' => $results,
		)
	);
}

/**
 * Create Credit Card Table at the time of plugin installation
 *
 * @return void
 */
function createCreditCardDB() {
	 global $wpdb;
	global $creditCardDBVersion;

	$tableName      = $wpdb->prefix . 'altapayCreditCardDetails';
	$charsetCollate = $wpdb->get_charset_collate();
	$sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            userID varchar(200) DEFAULT '' NOT NULL,
            cardBrand varchar(200) DEFAULT '' NOT NULL,
            creditCardNumber varchar(200) DEFAULT '' NOT NULL,
            cardExpiryDate varchar(200) DEFAULT '' NOT NULL,
            ccToken varchar(200) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
        ) $charsetCollate;";

	require_once ABSPATH . '/wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	add_option( '$creditCardDBVersion', $creditCardDBVersion );
}

/**
 * Add Dropdown list of saved credit cards in description of payment options on checkout page
 *
 * @param string $description
 * @param string $payment_id
 *
 * @return string
 */
function creditCardCustomDescription( $description, $payment_id ) {
	$gateways = WC()->payment_gateways->get_available_payment_gateways();

	$checkout = new WC_Checkout();
	if ( $gateways ) {
		foreach ( $gateways as $gateway ) {

			if ( $gateway->enabled === 'yes' && isset( $gateway->settings['token_control'] ) && $gateway->settings['token_control'] === 'yes' ) {

				if ( $gateway->id === $payment_id && is_user_logged_in() ) {
					ob_start();
					global $wpdb;

					$userID  = get_current_user_id();
					$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}altapayCreditCardDetails WHERE userID='$userID'" );

					$creditCard[] = _( 'Select a saved credit card' );
					foreach ( $results as $result ) {
						$creditCard[ $result->creditCardNumber ] = $result->creditCardNumber . ' (' . $result->cardExpiryDate . ')';
					}

					woocommerce_form_field(
						'savedCreditCard',
						array(
							'type'    => 'select',
							'class'   => array( 'wps-drop' ),
							'options' => $creditCard,
							'default' => '',
						),
						$checkout->get_value( 'savedCreditCard' )
					);
					$description .= ob_get_clean(); // Append buffered content
				}
			}
		}
	}

	return $description;
}


/**
 * Display Saved Credit Card Details button after successful order
 *
 * @param string   $text
 * @param WC_Order $order
 *
 * @return string
 */
function filterSaveCreditCardDetailsButton( $text, $order ) {
	global $wpdb;

	// Get payment methods
	$paymentMethods = WC()->payment_gateways->get_available_payment_gateways();

	$orderMeta          = get_metadata( 'post', $order->id );
	$cardNo             = $orderMeta['_cardno'][0];
	$ccToken            = $orderMeta['_credit_card_token'][0];
	$ccBrand            = $orderMeta['_credit_card_brand'][0];
	$ccExpiryDate       = $orderMeta['_credit_card_expiry_date'][0];
	$orderPaymentMethod = $orderMeta['_payment_method'][0];
	$buttonText         = _( 'Save your credit card for later use' );

	$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}altapayCreditCardDetails WHERE userID='" . get_current_user_id() . "' and ccToken='$ccToken'" );

	if ( array_key_exists( 'test', $_POST ) ) {
		saveCreditCardDetails( $cardNo, $ccToken, $ccBrand, $ccExpiryDate );
	}

	if ( ! empty( $orderMeta['_cardno'][0] ) && empty( $results ) && is_user_logged_in() ) {
		if ( $paymentMethods[ $orderPaymentMethod ]->settings['enabled'] === 'yes' && $paymentMethods[ $orderPaymentMethod ]->settings['token_control'] === 'yes' ) {
			return '<form method="post" action="">
		<input type="submit" name="test" id="test" value="' . $buttonText . '" /><br/>
		</form>';
		}
	}
}

/**
 * Save the credit card details in the database - Triggered when save credit card details button
 *
 * @param string $cardNo
 * @param string $ccToken
 * @param string $ccBrand
 * @param string $ccExpiryDate
 * @return void
 */
function saveCreditCardDetails( $cardNo, $ccToken, $ccBrand, $ccExpiryDate ) {
	global $wpdb;

	$wpdb->insert(
		'wp_' . 'altapayCreditCardDetails',
		array(
			'time'             => date( 'Y-m-d H:i:s' ),
			'userID'           => get_current_user_id(),
			'cardBrand'        => $ccBrand,
			'creditCardNumber' => $cardNo,
			'cardExpiryDate'   => $ccExpiryDate,
			'ccToken'          => $ccToken,
		)
	);
	header( 'Location: ' . wp_unslash( $_SERVER['REQUEST_URI'] ) );
}

/**
 * Method to set the savedCreditCard session variable for createPaymentRequest
 *
 * @return void
 */
function setCreditCardSessionVariable() {
	if ( isset( $_POST['savedCreditCard'] ) ) {
		WC()->session->set( 'cardNumber', sanitize_text_field( wp_unslash( $_POST['savedCreditCard'] ) ) );
	}
}
