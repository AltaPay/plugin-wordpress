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
	}

	/**
	 * Validate Apple Pay Session
	 *
	 * @return void
	 */
	public function applepay_validate_merchant() {
		$terminals   = json_decode( get_option( 'altapay_terminals' ) );
		$terminal_id = isset( $_POST['terminal_id'] ) ? wc_clean( wp_unslash( $_POST['terminal_id'] ) ) : '';

		$terminal = '';
		foreach ( $terminals  as $terminal ) {
			if ( 'altapay_' . strtolower( $terminal->key ) === $terminal_id ) {
				$terminal = $terminal->name;
				break;
			}
		}

		$request = new CardWalletSession( $this->getAuth() );
		$request->setTerminal( $terminal )
			->setValidationUrl( $_POST['validation_url'] )
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
			true
		);
		wp_enqueue_script(
			'altapay-applepay-main',
			plugin_dir_url( ALTAPAY_PLUGIN_FILE ) . 'assets/js/applepay.js',
			array( 'jquery', 'altapay-applepay-sdk' ),
			'1.0.0',
			true
		);

		$apple_pay_terminals = array();

		foreach ( WC()->payment_gateways->payment_gateways() as $key => $payment_gateway ) {
			if ( isset( $payment_gateway->settings['is_apple_pay'] ) && $payment_gateway->settings['is_apple_pay'] === 'yes' ) {
				$apple_pay_terminals[] = $key;
			}
		}

		wp_localize_script(
			'altapay-applepay-main',
			'applepay_ajax_obj',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'apple-pay' ),
				'currency'           => get_woocommerce_currency(),
				'country'            => get_option( 'woocommerce_default_country' ),
				'cart_totals'        => WC()->session->get( 'cart_totals', null ),
				'apple_pay_terminal' => $apple_pay_terminals,
			)
		);
	}

}
