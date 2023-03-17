<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Altapay\Classes\Core;

use Altapay\Api\Payments\CardWalletSession;
use Altapay\Api\Payments\CardWalletAuthorize;
use Altapay\Helpers\Traits\AltapayMaster;

class ApplePay {

	use AltapayMaster;

	/**
	 * Register required hooks
	 *
	 * @return void
	 */
	public function registerHooks() {
		add_action( 'wp_ajax_validate_merchant', array( $this, 'applepay_validate_merchant' ) );
		add_action( 'wp_ajax_nopriv_validate_merchant', array( $this, 'applepay_validate_merchant' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'altapay_load_apple_pay_script' ) );
		add_action( 'wp_ajax_card_wallet_authorize', array( $this, 'applepay_card_wallet_authorize' ) );
		add_action( 'wp_ajax_nopriv_card_wallet_authorize', array( $this, 'applepay_card_wallet_authorize' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_apple_pay_for_non_safari_browser' ), 10, 2 );
	}

	/**
	 * @param $payment_methods
	 * @return array
	 */
	public function filter_apple_pay_for_non_safari_browser( $payment_methods ) {

		$user_agent = $_SERVER['HTTP_USER_AGENT'];

		if ( is_checkout() ) {

			$is_safari = ( strpos( $user_agent, 'Safari' ) !== false && strpos( $user_agent, 'Chrome' ) === false );

			if ( ! $is_safari ) {
				foreach ( $payment_methods as $key => $payment_gateway ) {
					if ( isset( $payment_gateway->settings['is_apple_pay'] ) && $payment_gateway->settings['is_apple_pay'] === 'yes' ) {
						unset( $payment_methods[ $key ] );
					}
				}
			}
		}

		return $payment_methods;
	}

	/**
	 * Enqueue Apple Pay scripts
	 *
	 * @return void
	 */
	public function altapay_load_apple_pay_script() {

		wp_enqueue_script(
			'altapay-applepay-sdk',
			'https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js',
			array( 'jquery' ),
			'1.0.0',
			false
		);
		wp_enqueue_script(
			'altapay-applepay-main',
			plugin_dir_url( ALTAPAY_PLUGIN_FILE ) . 'assets/js/applepay.js',
			array( 'jquery', 'altapay-applepay-sdk' ),
			'1.0.0',
			false
		);
	}

	/**
	 * Validate Apple Pay Session
	 *
	 * @return void
	 */
	public function applepay_validate_merchant() {
		$terminal       = isset( $_POST['terminal'] ) ? sanitize_text_field( wp_unslash( $_POST['terminal'] ) ) : '';
		$validation_url = isset( $_POST['validation_url'] ) ? sanitize_text_field( wp_unslash( $_POST['validation_url'] ) ) : '';

		$request = new CardWalletSession( $this->getAuth() );
		$request->setTerminal( $terminal )
			->setValidationUrl( $validation_url )
			->setDomain( $_SERVER['HTTP_HOST'] );

		try {
			$response = $request->call();
			if ( $response->Result === 'Success' ) {
				wp_send_json_success( $response->ApplePaySession, 200 );
			} else {
				wp_send_json_error();
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 *  Call CardWalletAuthorize API
	 *
	 * @return void
	 */
	public function applepay_card_wallet_authorize() {
		$provider_data = isset( $_POST['provider_data'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_data'] ) ) : '';
		$terminal      = isset( $_POST['terminal'] ) ? sanitize_text_field( wp_unslash( $_POST['terminal'] ) ) : '';
		$order_id      = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
		$order         = wc_get_order( $order_id );

		$request = new CardWalletAuthorize( $this->getAuth() );
		$request->setTerminal( $terminal )
			->setProviderData( $provider_data )
			->setAmount( (float) $order->get_total() )
			->setCurrency( $order->get_currency() )
			->setShopOrderId( $order_id );

		try {
			$response = $request->call();

			$transactions       = json_decode( wp_json_encode( $response->Transactions ), true );
			$latest_transaction = $this->getLatestTransaction( $transactions, 'payment' );
			$txn_id             = $transactions[ $latest_transaction ]['TransactionId'];

			$order->add_order_note( __( 'Apple Pay payment completed', 'altapay' ) );
			$order->payment_complete();
			update_post_meta( $order_id, '_transaction_id', $txn_id );

			if ( $response->Result === 'Success' ) {
				wp_send_json_success(
					array(
						'redirect' => $order->get_checkout_order_received_url(),
						'response' => $response,
					),
					200
				);
			} else {
				wp_send_json_error();
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}
}
