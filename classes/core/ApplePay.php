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
}
