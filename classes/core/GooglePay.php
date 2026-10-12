<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2026 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Altapay\Classes\Core;

use Altapay\Api\Payments\CardWalletSession;
use Altapay\Api\Payments\CheckoutSession;
use Altapay\Request\Config;
use Altapay\Helpers\Traits\AltapayMaster;

class GooglePay {

	use AltapayMaster;

	/**
	 * The checkout session the current payment was registered with.
	 *
	 * @var string
	 */
	private $checkout_session_id = '';

	/**
	 * Register required hooks
	 *
	 * @return void
	 */
	public function registerHooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'altapay_load_google_pay_script' ) );
		add_action( 'woocommerce_review_order_after_submit', array( $this, 'googlepay_woocommerce_after_checkout_form' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'googlepay_cart_total_fragment' ) );
	}

	/**
	 * Enqueue Google Pay scripts
	 *
	 * @return void
	 */
	public function altapay_load_google_pay_script() {
		if ( ! is_checkout() && ! is_checkout_pay_page() ) {
			return;
		}

		wp_enqueue_script(
			'altapay-googlepay-sdk',
			'https://pay.google.com/gp/p/js/pay.js',
			array( 'jquery' ),
			'1.0.0',
			false
		);
		wp_enqueue_script(
			'altapay-googlepay-main',
			plugin_dir_url( ALTAPAY_PLUGIN_FILE ) . 'assets/js/googlepay.js',
			array( 'jquery', 'altapay-googlepay-sdk' ),
			'1.0.0',
			false
		);
	}

	/**
	 * Expose the Google Pay configuration to the classic checkout
	 *
	 * @return void
	 */
	public function googlepay_woocommerce_after_checkout_form() {

		$payment_gateways = WC()->payment_gateways()->payment_gateways();

		$googlepay_obj = array(
			'googlepay_payment_method' => '',
		);

		foreach ( $payment_gateways as $payment_gateway ) {
			if ( isset( $payment_gateway->is_google_pay ) && $payment_gateway->is_google_pay === 'yes' ) {
				$googlepay_obj = array_merge(
					array(
						'ajax_url'                 => admin_url( 'admin-ajax.php' ),
						'nonce'                    => wp_create_nonce( 'google-pay' ),
						'subtotal'                 => WC()->cart->get_total( 'edit' ),
						'terminal'                 => $payment_gateway->terminal,
						'googlepay_payment_method' => $payment_gateway->id,
					),
					self::getGooglePayConfig( $payment_gateway )
				);
				break;
			}
		}
		?>
		<script>
			var altapay_googlepay_obj = <?php echo wp_json_encode( $googlepay_obj ); ?>;
		</script>
		<?php
	}

	/**
	 * Google Pay parameters shared by the classic and the block based checkout
	 *
	 * @param WC_Payment_Gateway $payment_gateway
	 * @return array
	 */
	public static function getGooglePayConfig( $payment_gateway ) {

		$base_location = wc_get_base_location();
		$terminal      = AltapaySettings::getStoredTerminal( $payment_gateway->terminal );
		$wallet        = $terminal['method_configs']['GooglePay'] ?? array();
		$environment   = ! empty( $wallet['environment'] ) && $wallet['environment'] === 'PRODUCTION'
			? 'PRODUCTION'
			: 'TEST';

		return array(
			'currency'            => get_woocommerce_currency(),
			'country'             => $base_location['country'],
			'environment'         => $environment,
			'merchant_id'         => $environment === 'PRODUCTION' ? ( $wallet['merchant_id'] ?? '' ) : '',
			'merchant_name'       => ! empty( $wallet['merchant_name'] ) ? $wallet['merchant_name'] : get_bloginfo( 'name' ),
			'gateway'             => 'marketpay',
			'gateway_merchant_id' => $wallet['merchant_id'] ?? '',
			'supported_networks'  => $terminal['schemes'] ?? array(),
			'auth_methods'        => array( 'PAN_ONLY', 'CRYPTOGRAM_3DS' ),
		);
	}

	/**
	 * Register the payment with the terminal through the cardWallet/session API.
	 *
	 * @param WC_Order $order
	 * @param string   $terminal
	 * @return string The payment id, or an empty string when the session could not be created.
	 */
	public function createWalletSession( $order, $terminal, $browser_info = array() ) {
		try {
			$session_id = $this->getCheckoutSessionId( $order, $terminal );
		} catch ( \Exception $e ) {
			$order->add_order_note( __( 'Google Pay checkout session failed: ', 'altapay' ) . $e->getMessage() );

			return '';
		}
		
		if ( ! $session_id ) {
			$order->add_order_note( __( 'Google Pay checkout session did not return a session id.', 'altapay' ) );

			return '';
		}

		$this->checkout_session_id = $session_id;

		$request = new CardWalletSession( $this->getAuth() );
		$request->setTerminal( $terminal )
			->setShopOrderId( $order->get_id() )
			->setAmount( (float) $order->get_total() )
			->setCurrency( $order->get_currency() )
			->setSessionId( $session_id )
			->setConfig( $this->getCallbackConfig( $order ) );

		$customer = $this->getCustomerInfo( $order, $browser_info );

		if ( $customer ) {
			$request->setCustomerInfo( $customer );
		}

		try {
			$response = $request->call();
		} catch ( \Exception $e ) {
			$order->add_order_note( __( 'Google Pay session failed: ', 'altapay' ) . $e->getMessage() );

			return '';
		}

		if ( $response->Result !== 'Success' ) {
			$order->add_order_note( __( 'Google Pay session failed.', 'altapay' ) );

			return '';
		}

		$transaction = ! empty( $response->Transactions ) ? reset( $response->Transactions ) : null;

		if ( ! isset( $transaction->PaymentId ) ) {
			$order->add_order_note( __( 'Google Pay session did not return a payment id.', 'altapay' ) );

			return '';
		}

		$order->update_meta_data( 'altapay_payment_id', $transaction->PaymentId );
		$order->save();

		return $transaction->PaymentId;
	}

	/**
	 * Customer details for the wallet session, carrying the browser fingerprint.
	 *
	 * @param WC_Order             $order
	 * @param array<string, mixed> $browser_info
	 * @return Customer|null
	 */
	private function getCustomerInfo( $order, $browser_info ) {

		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = $gateways[ $order->get_payment_method() ] ?? null;

		if ( ! $gateway || ! method_exists( $gateway, 'setCustomer' ) ) {
			return null;
		}

		$customer = $gateway->setCustomer( $order );

		$customer->setClientJavascriptEnabled( true );
		$customer->setClientJavaEnabled( ! empty( $browser_info['java_enabled'] ) );

		if ( ! empty( $browser_info['screen_width'] ) ) {
			$customer->setClientScreenWidth( (string) $browser_info['screen_width'] );
		}

		if ( ! empty( $browser_info['screen_height'] ) ) {
			$customer->setClientScreenHeight( (string) $browser_info['screen_height'] );
		}

		if ( ! empty( $browser_info['color_depth'] ) ) {
			$customer->setClientColorDepth( (string) $browser_info['color_depth'] );
		}

		if ( isset( $browser_info['timezone'] ) && $browser_info['timezone'] !== '' ) {
			$customer->setClientTimeZone( (string) $browser_info['timezone'] );
		}

		if ( ! empty( $_SERVER['HTTP_ACCEPT'] ) ) {
			$customer->setClientAccept( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) );
		}

		return $customer;
	}

	/**
	 * The checkout session id of the payment registered by createWalletSession().
	 *
	 * @return string
	 */
	public function getSessionId() {
		return $this->checkout_session_id;
	}

	/**
	 * Register the external session id with the gateway and return the session it created.
	 *
	 * @param WC_Order $order
	 * @param string   $terminal
	 * @return string
	 * @throws \Exception
	 */
	private function getCheckoutSessionId( $order, $terminal ) {

		$order_id   = $order->get_id();
		$session_key = 'altapay_checkout_session_id_' . $order_id;
		$session_id = WC()->session ? WC()->session->get( $session_key ) : '';

		if ( ! empty( $session_id ) ) {
			return $session_id;
		}

		$checkout_session = new CheckoutSession( $this->getAuth() );
		$checkout_session->setTerminal( $terminal )
			->setTerminals( array( $terminal ) )
			->setShopOrderId( $order_id )
			->setAmount( round( (float) $order->get_total(), 2 ) )
			->setCurrency( $order->get_currency() )
			->setSessionId( wp_hash( $order->get_order_key() ) )
			->setConfig( $this->getCallbackConfig( $order ) );

		$response = $checkout_session->call();

		if ( ! isset( $response->Session->Id ) ) {
			return '';
		}

		if ( WC()->session ) {
			WC()->session->set( $session_key, $response->Session->Id );
		}

		return $response->Session->Id;
	}

	/**
	 * Callback URLs for the wallet calls.
	 *
	 * @param WC_Order $order
	 * @return Config
	 */
	private function getCallbackConfig( $order ) {

		$wc_api     = 'WC_Gateway_' . $order->get_payment_method();
		$return_url = $order->get_checkout_order_received_url();

		$config = new Config();
		$config->setCallbackOk(
			add_query_arg( array( 'type' => 'ok', 'wc-api' => $wc_api ), $return_url )
		);
		$config->setCallbackFail(
			add_query_arg( array( 'type' => 'fail', 'wc-api' => $wc_api ), $return_url )
		);

		return $config;
	}

	/**
	 * Keep the amount shown in the Google Pay sheet in sync with the cart total.
	 *
	 * @param array $fragments
	 * @return array
	 */
	public function googlepay_cart_total_fragment( $fragments ) {

		if ( WC()->cart ) {
			$fragments['altapay_cart_total'] = WC()->cart->get_total( 'edit' );
		}

		return $fragments;
	}
}
