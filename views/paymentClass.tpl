<?php
/**
 * AltaPay module for WooCommerce

 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Altapay\Helpers\Traits\AltapayMaster;
use Altapay\Classes\Util;
use Altapay\Classes\Core;
use Altapay\Helpers;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Request\Address;
use Altapay\Request\Customer;
use Altapay\Request\Config;
use Altapay\Exceptions\ClientException;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use Altapay\Api\Payments\CaptureReservation;
use Altapay\Api\Payments\ReleaseReservation;

class WC_Gateway_{key} extends WC_Payment_Gateway {

	use AltapayMaster;

    /**
     * Terminal name.
     *
     * @var string
     */
    public $terminal = '';

    /**
     * Terminal name.
     *
     * @var string
     */
    public $token = '';

    /**
     * Payment type.
     *
     * @var string
     */
    public $payment_action = '';

    /**
     * Currency for the terminal.
     *
     * @var string
     */
    private $currency;

    /**
     * Default currency for the terminal.
     *
     * @var string
     */
    private $default_currency;

	public function __construct() {
		// Set default gateway values
		$this->id					= strtolower('altapay_{key}');
		$this->has_fields			= false;
		$this->method_title			= 'AltaPay - {name}';
		$this->method_description	= __( 'Adds AltaPay Payment Gateway to use with WooCommerce', 'altapay');
		$this->supports = array(
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
		);

		$this->terminal			= '{name}';
		$this->enabled			= $this->get_option( 'enabled' );
		$this->title			= $this->get_option( 'title' );
		$this->description		= $this->get_option( 'description' );
		$this->token			= $this->get_option('token');
		$this->payment_action	= $this->get_option( 'payment_action' );
		$this->currency			= $this->get_option( 'currency' );
		$currency				= explode(' ', '{name}');
		$this->default_currency	= end($currency);

		if($this->get_option( 'payment_icon' ) !== 'default') {
			$this->icon = untrailingslashit( plugins_url( '/assets/images/payment_icons/'.$this->get_option( 'payment_icon' ), ALTAPAY_PLUGIN_FILE ) );
		}
		// Load form fields
		$this->init_form_fields();
		$this->init_settings();

		// Add actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page_altapay' ) );
		add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'checkAltapayResponse' ) );

		// Subscription actions
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduledSubscriptionsPayment'), 10, 2 );

	}

	/**
	 * Settings page
	 *
	 * @return void
	 */
	public function admin_options() {
		echo '<h3>{name}</h3>';
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	/**
	 * @param int $order_id
	 *
	 * @return void
	 * @throws Exception
	 */
	public function receipt_page_altapay( $order_id ) {
		// Show text
		$requestParams = $this->createPaymentRequest( $order_id );
		if ( is_wp_error( $requestParams ) ) {
			echo '<p>' . $requestParams->get_error_message() . '</p>';
		} else {
			echo '<script type="text/javascript">window.location.href = "' . $requestParams['formurl'] . '"</script>';
		}
	}

	/**
	 * Load form fields
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$tokenStatus = '{tokenStatus}';
		if($tokenStatus === 'CreditCard'){
			$this->form_fields = include dirname( ALTAPAY_PLUGIN_FILE ) . '/includes/AltapayFormFieldsToken.php';
		} else {
			$this->form_fields = include dirname( ALTAPAY_PLUGIN_FILE ) . '/includes/AltapayFormFields.php';
		}
	}

	/**
	 * @param int $order_id
	 *
	 * @return array|WP_Error
	 * @throws Exception
	 */
	public function createPaymentRequest( $order_id ) {
		global $wpdb;
		$utilMethods 	= new Util\UtilMethods;
		$altapayHelpers = new Helpers\AltapayHelpers;
		// Create form request etc.
		$login = $this->altapayApiLogin();
		if ( ! $login || is_wp_error( $login ) ) {
			return new WP_Error( 'error', 'Could not connect to AltaPay!' );
		}
		// Create payment request
		$order = new WC_Order( $order_id );

		// TODO Get terminal form instance
		$terminal 		= $this->terminal;
		$amount 		= $order->get_total();
		$currency		= $order->get_currency();
		$customerInfo	= $this->setCustomer( $order );
		$cookie 		= isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '';
		$language 		= 'en';
		$languages 		= array(
			'da_DK' => 'da',
			'sv_SE' => 'sv',
			'nn_NO' => 'no',
			'no_NO' => 'no',
			'nb_NO' => 'no',
			'de_DE' => 'de',
			'cs_CZ' => 'cs',
			'fi_FI' => 'fi',
			'fr_FR' => 'fr',
			'lt' 	=> 'lt',
			'nl_NL' => 'nl',
			'pl_PL' => 'pl',
			'et' 	=> 'et',
			'ee' 	=> 'et',
			'en_US' => 'en',
			'it' 	=> 'it',
		);
		if (array_key_exists(get_locale(), $languages)) {
			$language = $languages[get_locale()];
		}

		// Get chosen page from AltaPay's settings
		$form_page_id = esc_attr( get_option('altapay_payment_page') );
		$configUrl = array(
			'callback_form' 		=> get_page_link($form_page_id),
			'callback_ok' 			=> add_query_arg(
					array(
							'type' => 'ok',
							'wc-api' => 'WC_Gateway_'.$this->id,
					),
					$this->get_return_url($order)
			),
			'callback_fail'			=> add_query_arg(
					array(
							'type' => 'fail',
							'wc-api' => 'WC_Gateway_'.$this->id,
					),
					$this->get_return_url($order)
			),
			'callback_open' 		=> add_query_arg(
					array(
							'type' => 'open',
							'wc-api' => 'WC_Gateway_'.$this->id,
					),
					$this->get_return_url($order)
			),
			'callback_notification' => add_query_arg(
					array(
							'type' => 'notification',
							'wc-api' => 'WC_Gateway_'.$this->id,
					),
					$this->get_return_url($order)
			),
		);

		$config = new Config();
		$config->setCallbackOk( $configUrl['callback_ok'] );
		$config->setCallbackFail( $configUrl['callback_fail'] );
		$config->setCallbackOpen( $configUrl['callback_open'] );
		$config->setCallbackNotification( $configUrl['callback_notification'] );
		$config->setCallbackForm( $configUrl['callback_form'] );

		// Make these as settings
		$payment_type = 'payment';
		if ( $this->payment_action === 'authorize_capture' ) {
			$payment_type = 'paymentAndCapture';
		}

		$transactionInfo = $altapayHelpers->transactionInfo();

		// Add orderlines to AltaPay request
		$orderLines = $utilMethods->createOrderLines( $order );
		if ( $orderLines instanceof WP_Error ) {
			return $orderLines; // Some error occurred
		}

		try {
			$savedCardNumber = WC()->session->get( 'cardNumber', 0 );
			if ( !$savedCardNumber ) {
				$ccToken = null;
			} else {
				$results = $wpdb->get_results("SELECT ccToken FROM {$wpdb->prefix}altapayCreditCardDetails WHERE creditCardNumber='$savedCardNumber'");
				foreach ( $results as $result ) {
					$ccToken = $result->ccToken;
				}
			}

			mt_srand( crc32( serialize( array( microtime( true ), $order_id ) ) ) );
			$reconciliation_identifier = wp_generate_uuid4();
			add_post_meta( $order_id, '_reconciliation_identifier', $reconciliation_identifier , true);

			$auth    = $this->getAuth();
			$request = new PaymentRequest( $auth );
			$request->setTerminal( $terminal )
					->setShopOrderId( $order_id )
					->setAmount( round( $amount, 2 ) )
					->setCurrency( $currency )
					->setCustomerInfo( $customerInfo )
					->setConfig( $config )
					->setTransactionInfo( $transactionInfo )
					->setSalesTax( round( $order->get_total_tax(), 2 ) )
					->setCookie( $cookie )
					->setCcToken( $ccToken )
					->setFraudService( null )
					->setLanguage( $language )
					->setOrderLines( $orderLines )
					->setSaleReconciliationIdentifier( $reconciliation_identifier );

			// Check if WooCommerce subscriptions is enabled and contains subscription product
			if ( class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $order_id ) ) {
				if ( $this->payment_action === 'authorize_capture' ) {
					$payment_type = 'subscriptionAndCharge';
				} else {
					$payment_type = 'subscriptionAndReserve';
				}

				$request->setAgreement($this->getAgreementDetail($order));
			}

			$request->setType( $payment_type );

			if ( $request ) {
				try {
					$response                 = $request->call();
					$requestParams['result']  = 'success';
					$requestParams['formurl'] = $response->Url;
				} catch ( ClientException $e ) {
					$requestParams['result']  = 'error';
					$requestParams['message'] = $e->getResponse()->getBody();
				} catch ( ResponseHeaderException $e ) {
					$requestParams['result']  = 'error';
					$requestParams['message'] = $e->getHeader()->ErrorMessage;
				} catch ( ResponseMessageException $e ) {
					$requestParams['result']  = 'error';
					$requestParams['message'] = $e->getMessage();
				} catch ( \Exception $e ) {
					$requestParams['result']  = 'error';
					$requestParams['message'] = $e->getMessage();
				}
				if ( isset( $requestParams['message'] ) && $requestParams['result'] === 'error' ) {
					return new WP_Error( 'ResponseError', $requestParams['message'] );
				}

				echo '<p>' . __( 'You are now going to be redirected to AltaPay Payment Gateway', 'altapay' ) . '</p>';

				return $requestParams;
			}
		} catch ( Exception $e ) {
			error_log( 'Could not create the payment request: ' . $e->getMessage() );
			$order->add_order_note( __( 'Could not create the payment request: ' . $e->getMessage(), 'altapay' ) );

			return new WP_Error( 'error', 'Could not create the payment request' );
		}
	}

	/**
	 * Check for Gateway Response
	 *
	 * @return void
	 */
	public function checkAltapayResponse() {
		// Check if callback is altapay and the allowed API
		if ( isset( $_GET['wc-api'] ) && $_GET['wc-api'] === 'WC_Gateway_' . $this->id ) {

			$order_id       = isset( $_POST['shop_orderid'] ) ? sanitize_text_field( wp_unslash( $_POST['shop_orderid'] ) ) : '';
			$txnId          = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';
			$amount         = isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '';
			$merchantError  = isset( $_POST['merchant_error_message'] ) ? sanitize_text_field( wp_unslash( $_POST['merchant_error_message'] ) ) : '';
			$errorMessage   = isset( $_POST['error_message'] ) ? sanitize_text_field( wp_unslash( $_POST['error_message'] ) ) : '';
			$payment_status = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : '';
			$status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$type           = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$requireCapture = isset( $_POST['require_capture'] ) ? sanitize_text_field( wp_unslash( $_POST['require_capture'] ) ) : '';

			$order        = new WC_Order( $order_id );
			$agreement_id = '';

			$xmlResponse = wp_unslash( $_POST['xml'] );
			$xml         = new SimpleXMLElement( $xmlResponse );

			if ( $type === 'subscriptionAndCharge' || $type === 'subscriptionAndReserve' ) {
				$agreement_id      = $txnId;
				$xmlToJson         = wp_json_encode( $xml->Body->Transactions );
				$jsonToArray       = json_decode( $xmlToJson, true );
				$latestTransaction = $this->getLatestTransaction( $jsonToArray['Transaction'], 'subscription_payment' );
				$transaction       = $jsonToArray['Transaction'][ $latestTransaction ];
				$txnId             = $transaction['TransactionId'];

				// validate if both transaction agreement setup and reservation/capture are successful
				if ( $status === 'succeeded' ) {
					foreach ( $jsonToArray['Transaction'] as $transaction_data ) {
						if ( ( $transaction_data['AuthType'] === 'subscriptionAndReserve' &&
						$transaction_data['TransactionStatus'] !== 'recurring_confirmed' ) ||
						( $type === 'subscriptionAndReserve' && $transaction_data['AuthType'] === 'subscription_payment' &&
						$transaction_data['TransactionStatus'] !== 'preauth' ) ||
						( $type === 'subscriptionAndCharge' && $transaction_data['AuthType'] === 'subscription_payment' &&
						$transaction_data['TransactionStatus'] !== 'captured' )
						) {
							$order->add_order_note( __( 'Payment failed!', 'altapay' ) );
							wc_add_notice( __( 'Payment failed!', 'altapay' ), 'error' );
							wp_redirect( wc_get_cart_url() );
							exit;
						}
					}
				}
			} else {
				$xmlToJson   = wp_json_encode( $xml->Body->Transactions->Transaction );
				$transaction = json_decode( $xmlToJson, true );
			}

			$paymentScheme  = $transaction['PaymentSchemeName'];
			$lastFourDigits = $transaction['CardInformation']['LastFourDigits'] ?? '';
			$ccToken        = isset( $_POST['credit_card_token'] ) ? sanitize_text_field( wp_unslash( $_POST['credit_card_token'] ) ) : '';
			$saveCreditCard = isset( $_POST['transaction_info']['savecreditcard'] ) && sanitize_text_field( wp_unslash( $_POST['transaction_info']['savecreditcard'] ) );
			$ccExpiryDate = isset( $transaction['CreditCardExpiry'] ) ? ( $transaction['CreditCardExpiry']['Month'] . '/' . $transaction['CreditCardExpiry']['Year'] ) : '';
			$order = new WC_Order( $order_id );

			$transaction_id = get_post_meta( $order_id, '_transaction_id', true );

			/*
			Exit if payment already completed against the same order and 
			the new transaction ID is different
			*/
			if (! empty( $transaction_id ) && $transaction_id != $txnId ) {
				// Release duplicate transaction from the gateway side
				if ( $status === 'succeeded' ) {
					$api = new ReleaseReservation( $this->getAuth() );
					$api->setTransaction( $txnId );
					$api->call();
				}

				exit;
			}

			// If order already on-hold
			if ( $order->has_status( 'on-hold' ) ) {

				if ( $status === 'succeeded' ) {

					$order->add_order_note( __( 'Notification completed', 'altapay' ) );
					$order->payment_complete();

					update_post_meta( $order_id, '_transaction_id', $txnId );
					update_post_meta( $order_id, '_agreement_id', $agreement_id );

					if ( $saveCreditCard ) {
						$objTokenControl = new Core\AltapayTokenControl();
						$objTokenControl->saveCreditCardDetails( $order_id, $lastFourDigits, $ccToken, $paymentScheme, $ccExpiryDate );
					}

				} elseif ($status === 'error' || $status === 'failed') {
						$order->update_status( 'failed', 'Payment failed' . $errorMessage );
						$order->add_order_note( __( 'Payment failed' . $errorMessage . ' Merchant error: ' . $merchantError, 'altapay' ) );
				}

				exit;
			}

			if ( $status === 'open' ) {
				$order->update_status( 'on-hold', 'The payment is pending an update from the payment provider.' );
				$redirect = $this->get_return_url( $order );
				wp_redirect( $redirect );
				exit;
			}

			if ( $payment_status === 'released' ) {
				$order->add_order_note( __( 'Payment failed: payment released', 'altapay' ) );
				wc_add_notice( __( 'Payment error:', 'altapay' ) . ' Payment released', 'error' );
				wp_redirect( wc_get_cart_url() );
				exit;
			}

			// Make some validation
			if ( $errorMessage || $merchantError || array_key_exists( 'cancel_order', $_GET ) ) {
				$order->add_order_note( __( 'Payment failed: ' . $errorMessage . ' Merchant error: ' . $merchantError, 'altapay' ) );
				wc_add_notice( __( 'Payment error:', 'altapay' ) . ' ' . $errorMessage, 'error' );
				wp_redirect( wc_get_cart_url() );
				exit;
			}

			if ( $order->has_status( 'pending' ) && $status === 'succeeded' ) {
				// Payment completed
				$order->add_order_note( __( 'Callback completed', 'altapay' ) );
				$order->payment_complete();
				update_post_meta( $order_id, '_transaction_id', $txnId );
				update_post_meta( $order_id, '_agreement_id', $agreement_id );

				if ( $saveCreditCard ) {
					$objTokenControl = new Core\AltapayTokenControl();
					$objTokenControl->saveCreditCardDetails( $order_id, $lastFourDigits, $ccToken, $paymentScheme, $ccExpiryDate );
				}
			}

			// Redirect to Order Confirmation Page
			if ( $type === 'paymentAndCapture' && $requireCapture === 'true' ) {
				$login = $this->altapayApiLogin();
				if ( ! $login || is_wp_error( $login ) ) {
					echo '<p><b>' . __( 'Could not connect to AltaPay!', 'altapay' ) . '</b></p>';
					return;
				}

				$reconciliation_identifier = get_post_meta( $order_id, '_reconciliation_identifier', true );

				$api = new CaptureReservation( $this->getAuth() );
				$api->setAmount( round( $amount, 2 ) );
				$api->setTransaction( $txnId );
				if ( ! empty( $reconciliation_identifier ) ) {
					$api->setReconciliationIdentifier($reconciliation_identifier);
				}

				/** @var CaptureReservationResponse $response */
				try {
					$response = $api->call();
				} catch ( ResponseHeaderException $e ) {
					error_log( 'Response header exception ' . $e->getMessage() );
				} catch ( \Exception $e ) {
					error_log( 'Response header exception ' . $e->getMessage() );
				}
			}
			$redirect = $this->get_return_url( $order );
			wp_redirect( $redirect );
			exit;
		}
	}

	/**
	* Process refund.
	*
	* If the gateway declares 'refunds' support, this will allow it to refund.
	* a passed in amount.
	*
	* @param  int        $order_id Order ID.
	* @param  float|null $amount Refund amount.
	* @param  string     $reason Refund reason.
	* @return boolean True on success, or a WP_Error object.
	*/
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$refund = altapayRefundPayment( $order_id, $amount, $reason, false );

		if ( isset( $refund['error'] ) ) {
			return new WP_Error( 'error', __( $refund['error'], 'altapay' ) );
		}

		return true;
	}

	/**
	 * @param array   $addressInfo
	 * @param Address $address
	 *
	 * @return void
	 */
	private function populateAddressObject( $addressInfo, $address ) {
		$address->Firstname  = $addressInfo['firstname'];
		$address->Lastname   = $addressInfo['lastname'];
		$address->Address    = $addressInfo['address'];
		$address->City       = $addressInfo['city'];
		$address->PostalCode = $addressInfo['postcode'];
		$address->Region     = $addressInfo['region'] ?: '0';
		$address->Country    = $addressInfo['country'];
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return Customer
	 * @throws Exception
	 */
	public function setCustomer( $order ) {
		$address        = new Address();
		$altapayHelpers = new Helpers\AltapayHelpers();
		$billingInfo    = array(
			'firstname' => $order->get_billing_first_name(),
			'lastname'  => $order->get_billing_last_name(),
			'address'   => $order->get_billing_address_1(),
			'postcode'  => $order->get_billing_postcode(),
			'city'      => $order->get_billing_city(),
			'region'    => $order->get_billing_state(),
			'country'   => $order->get_billing_country(),
		);
		$shippingInfo   = array(
			'firstname' => $order->get_shipping_first_name(),
			'lastname'  => $order->get_shipping_last_name(),
			'address'   => $order->get_shipping_address_1(),
			'postcode'  => $order->get_shipping_postcode(),
			'city'      => $order->get_shipping_city(),
			'region'    => $order->get_shipping_state(),
			'country'   => $order->get_shipping_country(),
		);
		if ( $order->get_billing_address_1() ) {
			$this->populateAddressObject( $billingInfo, $address );
		}
		$customer = new Customer( $address );
		if ( $order->get_shipping_address_1() ) {
			$shippingAddress = new Address();
			$this->populateAddressObject( $shippingInfo, $shippingAddress );
			$customer->setShipping( $shippingAddress );
		} else {
			$customer->setShipping( $address );
		}
		$customer->setEmail( $order->get_billing_email() );
		$customer->setPhone( str_replace( ' ', '', $order->get_billing_phone() ) );
		$customer->setClientIP( $_SERVER['REMOTE_ADDR'] );
		$customer->setClientAcceptLanguage( substr( $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2 ) );
		$customer->setHttpUserAgent( $_SERVER['HTTP_USER_AGENT'] );
		$customer->setClientSessionID( crypt( session_id(), '$5$rounds=5000$customersessionid$' ) );

		// Get user registration date
		if ( is_user_logged_in() ) {
			$users         = get_users();
			$currentUserId = get_current_user_id();
			foreach ( $users as $user ) {
				$userData            = get_userdata( $currentUserId );
				$customerCreatedDate = $altapayHelpers->convertDateTimeFormat( $userData->user_registered );
				$customer->setCreatedDate( new \DateTime( $customerCreatedDate ) );
			}
		}

		return $customer;
	}

	/**
	 * @param $order
	 * @return array
	 */
	public function getAgreementDetail($order){
		$agreementDetails  = [];
			$agreementDetails['type'] = 'recurring';
			$subscriptions = wcs_get_subscriptions_for_order( $order );

			foreach ( $subscriptions as $subscription ) {
				$agreementDetails['frequency'] = $this->getAgreementFrequency($subscription->get_billing_period());
				$agreementDetails['next_charge_date'] = date("Ymd", $subscription->get_time('next_payment'));
				$agreementDetails['admin_url'] = $subscription->get_view_order_url();

				if($subscription->get_time('end')){
					$agreementDetails['expiry'] = date("Ymd", $subscription->get_time('end'));
				}
			}

		return $agreementDetails;
	}

	/**
	 * @param $billing_period
	 * @return string
	 */
	public function getAgreementFrequency($billing_period){

		$arr = array(
		'day'=> '1',
		'week'=> '7',
		'month'=> '30',
		'year'=> '365'
		);

		return $arr[$billing_period] ?? '30';
	}
}
