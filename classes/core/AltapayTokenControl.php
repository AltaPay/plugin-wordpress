<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Altapay\Classes\Core;

use Altapay\Helpers;
use WC_Checkout;

class AltapayTokenControl {


	/**
	 * Register required hooks
	 *
	 * @return void
	 */
	public function registerHooks() {
		// Respective filters for binding functionality with WordPress are added
		add_filter( 'woocommerce_account_menu_items', array( $this, 'savedCreditCardMenuLink' ), 40 );
		add_action( 'init', array( $this, 'savedCreditCardEndpoint' ) );
		add_action( 'woocommerce_account_saved-credit-cards_endpoint', array( $this, 'savedCreditCardEndpointContent' ) );
		add_filter( 'woocommerce_gateway_description', array( $this, 'creditCardCustomDescription' ), 99, 2 );
		add_filter(
			'woocommerce_thankyou_order_received_text',
			array( $this, 'filterSaveCreditCardDetailsButton' ),
			10,
			2
		);
		add_action( 'woocommerce_checkout_process', array( $this, 'setCreditCardSessionVariable' ) );
		add_action ('wp_loaded', array( $this, 'altapay_saved_credit_card_actions' ) );
	}

	/**
	 * Add Link (Tab) to My Account menu
	 *
	 * @param array $menuLinks
	 *
	 * @return array
	 */
	public function savedCreditCardMenuLink( $menuLinks ) {
		$menuLinks = array_slice( $menuLinks, 0, 5, true )
					 + array( 'saved-credit-cards' => 'Saved credit card(s)' )
					 + array_slice( $menuLinks, 5, null, true );

		return $menuLinks;

	}

	/**
	 * Register Permalink Endpoint
	 *
	 * @return void
	 */
	public function savedCreditCardEndpoint() {
		add_rewrite_endpoint( 'saved-credit-cards', EP_PAGES );
		flush_rewrite_rules();
	}

	/**
	 * Content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
	 *
	 * @return void
	 * @throws Exception
	 */
	public function savedCreditCardEndpointContent() {
		global $wpdb;

		$userID  = get_current_user_id();
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}altapayCreditCardDetails WHERE userID='$userID'" );
		$blade   = new Helpers\AltapayHelpers();
		echo _( '<h3>Saved credit card(s)</h3>' );

		if($results){
			echo $blade->loadBladeLibrary()->run(
				'tables.creditCard',
				array(
					'results' => $results,
				)
			);
		}else{
			echo _( 'No saved credit cards.' );
		}
	}

	/**
	 * Add Dropdown list of saved credit cards in description of payment options on checkout page
	 *
	 * @param string $description
	 * @param string $payment_id
	 *
	 * @return string
	 */
	public function creditCardCustomDescription( $description, $payment_id ) {
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$checkout = new WC_Checkout();
		if ( $gateways ) {
			foreach ( $gateways as $gateway ) {

				if ( $gateway->enabled === 'yes' && isset( $gateway->settings['token_control'] )
					&& $gateway->settings['token_control'] === 'yes'
				) {

					if ( $gateway->id === $payment_id && is_user_logged_in() ) {
						ob_start();
						global $wpdb;

						$userID  = get_current_user_id();
						$results
								= $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}altapayCreditCardDetails WHERE userID='$userID'" );

						$creditCard[] = _( 'Select a saved credit card' );
						foreach ( $results as $result ) {
							$creditCard[ $result->creditCardNumber ] = $result->creditCardNumber; // masked credit card number
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
	public function filterSaveCreditCardDetailsButton( $text, $order ) {
		global $wpdb;

		// Get payment methods
		$paymentMethods = WC()->payment_gateways->get_available_payment_gateways();

		$orderMeta          = get_metadata( 'post', $order->get_id() );
		$maskedCCNo			= $orderMeta['_cardno'][0]; // masked credit card number
		$ccToken            = $orderMeta['_credit_card_token'][0];
		$ccBrand            = $orderMeta['_credit_card_brand'][0];
		$ccExpiryDate       = $orderMeta['_credit_card_expiry_date'][0];
		$orderPaymentMethod = $orderMeta['_payment_method'][0];
		$buttonText         = _( 'Save your credit card for later use' );

		$results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}altapayCreditCardDetails WHERE userID='"
									  . get_current_user_id() . "' and ccToken='$ccToken'"
		);

		if ( array_key_exists( 'save_credit_card', $_POST ) ) {
			$this->saveCreditCardDetails( $maskedCCNo, $ccToken, $ccBrand, $ccExpiryDate );
		}

		if ( ! empty( $orderMeta['_cardno'][0] ) && ! $results && is_user_logged_in() ) {
			if ( $paymentMethods[ $orderPaymentMethod ]->settings['enabled'] === 'yes'
				&& $paymentMethods[ $orderPaymentMethod ]->settings['token_control'] === 'yes'
			) {
				return '<form method="post" action="">
		<input type="submit" name="save_credit_card" id="save_credit_card" value="' . $buttonText . '" /><br/>
		</form>';
			}
		}
	}

	/**
	 * Save the credit card details in the database - Triggered when save credit card details button
	 *
	 * @param string $maskedCCNo
	 * @param string $ccToken
	 * @param string $ccBrand
	 * @param string $ccExpiryDate
	 *
	 * @return void
	 */
	public function saveCreditCardDetails( $maskedCCNo, $ccToken, $ccBrand, $ccExpiryDate ) {
		global $wpdb;

		$wpdb->insert(
			'wp_' . 'altapayCreditCardDetails',
			array(
				'time'             => date( 'Y-m-d H:i:s' ),
				'userID'           => get_current_user_id(),
				'cardBrand'        => $ccBrand,
				'creditCardNumber' => $maskedCCNo,
				'cardExpiryDate'   => $ccExpiryDate,
				'ccToken'          => $ccToken,
			)
		);
		// Redirect user to Saved credit card(s) section of my account page
		wp_redirect( wc_get_endpoint_url( 'saved-credit-cards', '', get_permalink( wc_get_page_id( 'myaccount' ) ) ) );
		exit;
	}

	/**
	 * Method to set the savedCreditCard session variable for createPaymentRequest
	 *
	 * @return void
	 */
	public function setCreditCardSessionVariable() {
		if ( isset( $_POST['savedCreditCard'] ) ) {
			WC()->session->set( 'cardNumber', sanitize_text_field( wp_unslash( $_POST['savedCreditCard'] ) ) );
		}
	}

	/**
	 * 
	 * Method to handle the delete action against the saved credit card.
	 * 
	 * @return void
	 */

	function altapay_saved_credit_card_actions() {
		global $wpdb;
	
		if (isset($_GET['delete_card'])) {
			$wpdb->delete($wpdb->prefix . 'altapayCreditCardDetails', array('creditCardNumber' => $_GET['delete_card'], 'userID' => get_current_user_id()));
			wp_redirect( wc_get_endpoint_url( 'saved-credit-cards', '', get_permalink( wc_get_page_id( 'myaccount' ) ) ) );
			exit;
		}
	}
}
