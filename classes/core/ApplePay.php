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
use Altapay\Helpers;
use Altapay\Classes\Util;
use Altapay\Classes\Core;

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
		add_action( 'woocommerce_review_order_after_submit', array( $this, 'applepay_woocommerce_after_checkout_form' ) );
		add_filter( 'woocommerce_payment_successful_result', array( $this, 'func_woocommerce_payment_successful_result' ), 10, 2 );
	}

	/**
	 * @param $payment_methods
	 * @return array
	 */
	public function filter_apple_pay_for_non_safari_browser( $payment_methods ) {

		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		if ( is_checkout() || ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' )) {

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
		if ( ! is_checkout() && ! is_checkout_pay_page() ) {
			return;
		}

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
	 * Whether the Apple Pay has the legacy flow enabled.
	 *
	 * @param string $payment_method.
	 * @return bool
	 */
	private function isLegacyApplePayFlow( $payment_method ) {

		if ( ! $payment_method ) {
			return true;
		}

		$settings = get_option( 'woocommerce_' . $payment_method . '_settings' );

		if ( ! is_array( $settings ) ) {
			return true;
		}

		return ( $settings['apple_pay_legacy_flow'] ?? 'yes' ) === 'yes';
	}

	/**
	 * Validate Apple Pay Session
	 *
	 * @return void
	 */
	public function applepay_validate_merchant() {

		if ( ! wp_verify_nonce( wp_unslash( $_POST['ajax_nonce'] ), 'apple-pay' ) ) {
			wc_add_notice( __( 'Payment failed. Please try again.', 'altapay' ), 'error' );
			wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
		}

		$terminal       = isset( $_POST['terminal'] ) ? sanitize_text_field( wp_unslash( $_POST['terminal'] ) ) : '';
		$validation_url = isset( $_POST['validation_url'] ) ? sanitize_text_field( wp_unslash( $_POST['validation_url'] ) ) : '';
		$applepay_payment_method = isset( $_POST['applepay_payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['applepay_payment_method'] ) ) : '';
		$order_id       = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order          = $order_id ? wc_get_order( $order_id ) : false;

		$request = new CardWalletSession( $this->getAuth() );
		$request->setTerminal( $terminal )
			->setValidationUrl( $validation_url )
			->setDomain( $_SERVER['HTTP_HOST'] );

		if ( ! $this->isLegacyApplePayFlow( $applepay_payment_method ) ) {
			$request->setShopOrderId( $_POST['order_id'] )
				->setAmount( (float) $_POST['amount'] )
				->setCurrency( $_POST['currency'] )
				->setApplePayRequestData( [
					'validationUrl' => $validation_url,
					'domain'        => $_SERVER['HTTP_HOST']
				] );
		}

		try {
			$response = $request->call();
			$this->sendValidateMerchantResponse( $response, $order );
		} catch ( \Exception $e ) {
			wc_add_notice( __( 'Payment failed:', 'altapay' ) . ' ' . $e->getMessage(), 'error' );
			wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
		}
	}

	/**
	 * Send the AJAX response for a CardWalletSession result.
	 *
	 * @param object         $response
	 * @param WC_Order|false $order
	 * @return void
	 */
	private function sendValidateMerchantResponse( $response, $order ) {

		if ( $response->Result !== 'Success' ) {
			wc_add_notice( __( 'Payment failed.', 'altapay' ), 'error' );
			wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
		}

		if ( isset( $response->ApplePaySession ) ) {
			wp_send_json_success( $response->ApplePaySession, 200 );
		}

		if ( isset( $response->WalletData->Session ) ) {
			$transaction = ! empty( $response->Transactions ) ? reset( $response->Transactions ) : null;

			if ( $order && isset( $transaction->PaymentId ) ) {
				$order->update_meta_data( 'altapay_payment_id', $transaction->PaymentId );
				$order->save();
			}
			wp_send_json_success( $response->WalletData->Session, 200 );
		}

		wc_add_notice( __( 'Payment failed.', 'altapay' ), 'error' );
		wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
	}

	/**
	 *  Call CardWalletAuthorize API
	 *
	 * @return void
	 */
	public function applepay_card_wallet_authorize() {

		if ( ! wp_verify_nonce( wp_unslash( $_POST['ajax_nonce'] ), 'apple-pay' ) ) {
			wc_add_notice( __( 'Payment failed. Please try again.', 'altapay' ), 'error' );
			wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
		}

		$provider_data = isset( $_POST['provider_data'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_data'] ) ) : '';
		$terminal      = isset( $_POST['terminal'] ) ? sanitize_text_field( wp_unslash( $_POST['terminal'] ) ) : '';
		$applepay_payment_method = isset( $_POST['applepay_payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['applepay_payment_method'] ) ) : '';
		$order_id      = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
		$order         = wc_get_order( $order_id );

		$legacy_flow = $this->isLegacyApplePayFlow( $applepay_payment_method );

		if ( ! $legacy_flow ) {
			$altapay_payment_id = $order->get_meta( 'altapay_payment_id' );
		}

		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$payment_method   = $order->get_payment_method();

		$altapay_helpers                 = new Helpers\AltapayHelpers();
		$utils                           = new Util\UtilMethods();
		$transaction_info                = $altapay_helpers->transactionInfo();
		$transaction_info['ecomOrderId'] = $order->get_order_number();

		// Add order lines to AltaPay request
		$order_lines = $utils->createOrderLines( $order );

		$cookie = $_SERVER['HTTP_COOKIE'] ?? '';

		$request = new CardWalletAuthorize( $this->getAuth() );
		$request->setTerminal( $terminal )
			->setProviderData( $provider_data )
			->setShopOrderId( $order_id )
			->setAmount( (float) $order->get_total() )
			->setCurrency( $order->get_currency() )
			->setSalesTax( round( $order->get_total_tax(), 2 ) )
			->setTransactionInfo( $transaction_info )
			->setCookie( $cookie )
			->setOrderLines( $order_lines )
			->setSaleReconciliationIdentifier( wp_generate_uuid4() );

		if ( ! $legacy_flow ) {
			$request->setPaymentId( $altapay_payment_id );
		}

		$payment_type = $this->determinePaymentType( $payment_gateways, $payment_method );

		$request->setType( $payment_type );

		try {
			$response = $request->call();
			$this->sendCardWalletAuthorizeResponse( $response, $order, $order_id, $payment_type );
		} catch ( \Exception $e ) {
			$order->add_order_note( __( 'Payment failed: ' . $e->getMessage() ) );
			wc_add_notice( __( 'Payment failed:', 'altapay' ) . ' ' . $e->getMessage(), 'error' );
			wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
		}
	}

	/**
	 * Whether the gateway used for this order is set to authorize-and-capture.
	 *
	 * @param array  $payment_gateways
	 * @param string $payment_method
	 * @return string
	 */
	private function determinePaymentType( $payment_gateways, $payment_method ) {

		$payment_type = 'payment';

		foreach ( $payment_gateways as $key => $payment_gateway ) {
			if ( $key === $payment_method ) {
				if ( $payment_gateway->payment_action === 'authorize_capture' ) {
					$payment_type = 'paymentAndCapture';
				}
				break;
			}
		}

		return $payment_type;
	}

	/**
	 * Send the AJAX response for a CardWalletAuthorize result.
	 *
	 * @param object   $response
	 * @param WC_Order $order
	 * @param string   $order_id
	 * @param string   $payment_type
	 * @return void
	 */
	private function sendCardWalletAuthorizeResponse( $response, $order, $order_id, $payment_type ) {

		$transactions       = json_decode( wp_json_encode( $response->Transactions ), true );
		$latest_transaction = $this->getLatestTransaction( $transactions, $payment_type );
		$transaction        = $transactions[ $latest_transaction ];
		$txn_id             = $transaction['TransactionId'];

		$order->add_order_note( __( "Gateway Order ID: $order_id", 'altapay' ) );

		if ( $response->Result !== 'Success' ) {
			$order->add_order_note( __( 'Payment failed.' ) );
			wc_add_notice( __( 'Payment failed.', 'altapay' ), 'error' );
			wp_send_json_error( array( 'redirect' => wc_get_cart_url() ) );
		}

		$order->set_transaction_id( $txn_id );
		$order->add_order_note( __( 'Apple Pay payment completed', 'altapay' ) );
		$order->payment_complete();

		$reconciliation = new Core\AltapayReconciliation();
		foreach ( $transaction['ReconciliationIdentifiers'] as $val ) {
			$reconciliation->saveReconciliationIdentifier( $order_id, $txn_id, $val['Id'], $val['Type'] );
		}

		wp_send_json_success(
			array(
				'redirect' => $order->get_checkout_order_received_url(),
			),
			200
		);
	}

	/**
	 * @return void
	 */
	public function applepay_woocommerce_after_checkout_form() {

		$payment_gateways = WC()->payment_gateways()->payment_gateways();

		$applepay_obj = array(
			'applepay_payment_method' => ''
		);

		foreach ($payment_gateways as $key => $payment_gateway) {
			if (isset($payment_gateway->settings['is_apple_pay']) && $payment_gateway->settings['is_apple_pay'] === 'yes') {
				$applepay_obj = array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('apple-pay'),
					'currency' => get_woocommerce_currency(),
					'country' => get_option('woocommerce_default_country'),
					'subtotal' => WC()->cart->get_total( 'edit' ),
					'terminal' => $payment_gateway->terminal,
					'apply_pay_label' => $payment_gateway->apple_pay_label,
					'apple_pay_legacy_flow' => $payment_gateway->apple_pay_legacy_flow,
					'apple_pay_supported_networks' => $payment_gateway->get_option('apple_pay_supported_networks'),
					'applepay_payment_method' => $payment_gateway->id
				);
				break;
			}
		}
		?>
		<script>
			var altapay_applepay_obj = <?php echo json_encode($applepay_obj); ?>;
		</script>
		<?php
	}

	/**
	 * Return Order total to be used for Apple Pay js
	 * 
	 * @param int $order_id
	 * @param array $result
	 * @return void
	 */
	public function func_woocommerce_payment_successful_result($result, $order_id) {
		
		$order = wc_get_order( $order_id );
		$result['order_total'] = $order->get_total();

		return $result;
	}
}
