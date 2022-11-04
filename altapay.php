<?php
/**
 * Plugin Name: Altapay for WooCommerce - Payments less complicated
 * Plugin URI: https://documentation.altapay.com/Content/Plugins/Plugins.htm
 * Description: Payment Gateway to use with WordPress WooCommerce
 * Author: AltaPay
 * Author URI: https://altapay.com
 * Version: 3.3.4
 * Name: SDM_Altapay
 * WC requires at least: 3.9.0
 * WC tested up to: 7.0.1
 *
 * @package Altapay
 */

use Altapay\Classes\Core;
use Altapay\Classes\Util;
use Altapay\Helpers;
use Altapay\Api\Payments\CaptureReservation;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Api\Payments\RefundCapturedReservation;
use Altapay\Api\Payments\ReleaseReservation;
use Altapay\Response\ReleaseReservationResponse;
use Altapay\Api\Others\Payments;
use Altapay\Api\Subscription\ChargeSubscription;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'ALTAPAY_PLUGIN_FILE' ) ) {
	define( 'ALTAPAY_PLUGIN_FILE', __FILE__ );
}

// Include the autoloader, so we can dynamically include the rest of the classes.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

/**
 * Init AltaPay settings and gateway
 *
 * @return void
 */
function init_altapay_settings() {
	// Make sure WooCommerce and WooCommerce gateway is enabled and loaded
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	$settings = new Core\AltapaySettings();
	// Add Gateway to WooCommerce if enabled
	if ( json_decode( get_option( 'altapay_terminals_enabled' ) ) ) {
		add_filter( 'woocommerce_payment_gateways', 'altapay_add_gateway' );
	}

	$objTokenControl = new Core\AltapayTokenControl();
	$objTokenControl->registerHooks();

	$objOrderStatus = new Core\AltapayOrderStatus();
	$objOrderStatus->registerHooks();
}

/**
 * Add the Gateway to WooCommerce.
 *
 * @param array<int, string> $methods
 *
 * @return array<int, string>
 */
function altapay_add_gateway( $methods ) {
	$pluginDir = plugin_dir_path( __FILE__ );
	// Directory for the terminals
	$terminalDir = $pluginDir . 'terminals/';
	// Temp dir in case the one from above is not writable
	$tmpDir = sys_get_temp_dir();
	// Get enabled terminals
	$terminals = json_decode( get_option( 'altapay_terminals_enabled' ) );
	// Load Terminal information
	$terminalInfo = json_decode( get_option( 'altapay_terminals' ) );
	if ( $terminals ) {
		foreach ( $terminals as $terminal ) {
			$tokenStatus   = '';
			$subscriptions = false;
			$terminalName  = $terminal;
			foreach ( $terminalInfo as $term ) {
				if ( $term->key === $terminal ) {
					$terminalName = $term->name;
					$natures      = array_column( json_decode( json_encode( $term->nature ), true ), 'Nature' );

					if ( ! count( array_diff( $natures, array( 'CreditCard' ) ) ) ) {
						$subscriptions = true;
					} elseif ( in_array( 'CreditCard', $natures, true ) ) {
						$tokenStatus = 'CreditCard';
					}
				}
			}

			// Check if file exists
			$path    = $terminalDir . $terminal . '.class.php';
			$tmpPath = $tmpDir . '/' . $terminal . '.class.php';

			if ( file_exists( $path ) ) {
				require_once $path;
				$methods[] = 'WC_Gateway_' . $terminal;
			} elseif ( file_exists( $tmpPath ) ) {
				require_once $tmpPath;
				$methods[] = 'WC_Gateway_' . $terminal;
			} else {
				// Create file
				$template = file_get_contents( $pluginDir . 'views/paymentClass.tpl' );
				$filename = $terminalDir . $terminal . '.class.php';
				// Check if terminals folder is writable or use tmp as fallback
				if ( ! is_writable( $terminalDir ) ) {
					$filename = $tmpDir . '/' . $terminal . '.class.php';
				}
				// Replace patterns
				$content = str_replace( array( '{key}', '{name}', '{tokenStatus}', '{supportSubscriptions}' ), array( $terminal, $terminalName, $tokenStatus, $subscriptions ), $template );

				file_put_contents( $filename, $content );
			}
		}
	}
	return $methods;
}

/**
 * Load payment template
 *
 * @param string $template Template to load.
 *
 * @return string
 */
function altapay_page_template( $template ) {
	// Get payment form page id
	$paymentFormPageID = esc_attr( get_option( 'altapay_payment_page' ) );
	if ( $paymentFormPageID && is_page( $paymentFormPageID ) ) {
		// Make sure the template is only loaded from AltaPay.
		// Load template override
		$template = locate_template( 'altapay-payment-form.php' );

		// If no template override load template from plugin
		if ( ! $template ) {
			$template = __DIR__ . '/views/altapay-payment-form.php';
		}
	}

	return $template;
}

/**
 * Register meta box for order details page
 *
 * @return bool
 */
function altapayAddMetaBoxes() {
	global $post;

	if ( $post->post_type !== 'shop_order' ) {
		return true;
	}
	// Load order
	$order         = new WC_Order( $post->ID );
	$paymentMethod = $order->get_payment_method();

	// Only show on AltaPay orders
	if ( strpos( $paymentMethod, 'altapay' ) !== false || strpos( $paymentMethod, 'valitor' ) !== false ) {
		add_meta_box(
			'altapay-actions',
			__( 'AltaPay actions', 'altapay' ),
			'altapay_meta_box',
			'shop_order',
			'normal'
		);

		add_meta_box(
			'altapay-order-reconciliation-identifier',
			__( 'Reconciliation Details', 'altapay' ),
			'altapay_order_reconciliation_identifier_meta_box',
			'shop_order',
			'normal'
		);
	}

	return true;
}

/**
 * Meta box display callback
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function altapay_meta_box( $post ) {
	// Load order
	$order        = new WC_Order( $post->ID );
	$txnID        = $order->get_transaction_id();
	$agreement_id = get_post_meta( $post->ID, '_agreement_id', true );

	if ( $txnID || $agreement_id ) {
		$settings = new Core\AltapaySettings();
		$login    = $settings->altapayApiLogin();

		if ( ! $login || is_wp_error( $login ) ) {
			echo '<p><b>' . __( 'Could not connect to AltaPay!', 'altapay' ) . '</b></p>';
			return;
		}

		if ( ! $txnID ) {
			$txnID = $agreement_id;
		}

		$auth = $settings->getAuth();
		$api  = new Payments( $auth );
		$api->setTransaction( $txnID );
		$payments = $api->call();

		$args     = array(
			'posts_per_page' => -1,
			'post_type'      => 'altapay_captures',
			'post_status'    => 'captured',
			'post_parent'    => $order->get_id(),
			'meta_query'     => array(
				array(
					'key'     => 'qty_captured',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'item_id',
					'compare' => 'EXISTS',
				),
			),
		);
		$captures = new WP_Query( $args );

		$itemsCaptured = array();

		if ( $captures->have_posts() ) {
			while ( $captures->have_posts() ) {
				$captures->the_post();
				if ( isset( $itemsCaptured[ get_post_meta( get_the_ID(), 'item_id', true ) ] ) ) {
					$itemsCaptured[ get_post_meta( get_the_ID(), 'item_id', true ) ] += get_post_meta( get_the_ID(), 'qty_captured', true );
				} else {
					$itemsCaptured[ get_post_meta( get_the_ID(), 'item_id', true ) ] = get_post_meta( get_the_ID(), 'qty_captured', true );
				}
			}

			wp_reset_postdata();
		}

		if ( $payments ) {
			foreach ( $payments as $pay ) {
				$reserved = $pay->ReservedAmount;
				$captured = $pay->CapturedAmount;
				$refunded = $pay->RefundedAmount;
				$status   = $pay->TransactionStatus;

				if ( $status === 'released' ) {
					echo '<br /><b>' . __( 'Payment released', 'altapay' ) . '</b>';
				} else {
					$charge = $reserved - $captured - $refunded;
					if ( $charge <= 0 ) {
						$charge = 0.00;
					}
					$blade = new Helpers\AltapayHelpers();
					echo $blade->loadBladeLibrary()->run(
						'tables.index',
						array(
							'reserved'       => $reserved,
							'captured'       => $captured,
							'charge'         => $charge,
							'refunded'       => $refunded,
							'order'          => $order,
							'items_captured' => $itemsCaptured,
						)
					);
				}
			}
		}
	} else {
		esc_html_e( 'Order got no transaction', 'altapay' );
	}
}

/**
 * Meta box display callback for AltaPay reconciliation identifier
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function altapay_order_reconciliation_identifier_meta_box( $post ) {

	$settings = new Core\AltapaySettings();
	$login    = $settings->altapayApiLogin();

	if ( ! $login || is_wp_error( $login ) ) {
		echo '<p>' . __( 'Could not connect to AltaPay!', 'altapay' ) . '</p>';
		return;
	}

	$postID = $post->ID;

	if ( $post->post_type === 'altapay_captures' ) {
		$postID = wp_get_post_parent_id( $postID );
	}

	$auth = $settings->getAuth();

	$order = new WC_Order( $postID );
	$txnID = $order->get_transaction_id();

	if ( $txnID ) {
		$api = new Payments( $auth );
		$api->setTransaction( $txnID );
		$payments = $api->call();
		?>
		<table width="100%" cellspacing="0" cellpadding="10">
		   <thead>
		   <tr>
			   <th align="left" width="40%">Reconciliation Identifier</th>
			   <th align="left" width="60%">Type</th>
		   </tr>
		   </thead>
			<tbody>
				<?php
				foreach ( $payments as $payment ) {
					foreach ( $payment->ReconciliationIdentifiers as $identifier ) {
						?>
						<tr>
							<td><?php echo $identifier->Id; ?></td>
							<td><?php echo $identifier->Type; ?></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}
}

/**
 * Add scripts for the order details page
 *
 * @return void
 */
function altapayActionJavascript() {
	global $post;
	if ( isset( $post->ID ) ) {
		$postID = $post->ID;

		// Check if WooCommerce order
		if ( $post->post_type === 'shop_order' || $post->post_type === 'altapay_captures' ) {

			if ( $post->post_type === 'altapay_captures' ) {
				$postID = wp_get_post_parent_id( $postID );
			}
			?>
			<script type="text/javascript">
				let Globals = <?php echo wp_json_encode( array( 'postId' => $postID ) ); ?>;
			</script>
			<?php
			wp_enqueue_script(
				'captureScript',
				plugin_dir_url( __FILE__ ) . 'assets/js/capture.js',
				array( 'jquery' ),
				'1.1.0',
				true
			);
			wp_register_script(
				'jQuery',
				'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js',
				array(),
				'2.1.3',
				true
			);
			wp_enqueue_script( 'jQuery' );
			wp_enqueue_script(
				'refundScript',
				plugin_dir_url( __FILE__ ) . 'assets/js/refund.js',
				array( 'jquery' ),
				'1.1.0',
				true
			);
			wp_enqueue_script(
				'releaseScript',
				plugin_dir_url( __FILE__ ) . 'assets/js/release.js',
				array( 'jquery' ),
				'1.1.0',
				true
			);
		}
	}
}

/**
 * Method for creating payment page on call back
 *
 * @return void
 */
function createAltapayPaymentPageCallback() {
	global $userID;

	// Create page data
	$page = array(
		'post_type'    => 'page',
		'post_content' => '',
		'post_parent'  => 0,
		'post_author'  => $userID,
		'post_status'  => 'publish',
		'post_title'   => 'AltaPay payment form',
	);

	// Create page
	$pageID = wp_insert_post( $page );
	if ( $pageID == 0 ) {
		echo wp_json_encode(
			array(
				'status'  => 'error',
				'message' => __(
					'Error creating page, try again',
					'altapay'
				),
			)
		);
	} else {
		echo wp_json_encode(
			array(
				'status'  => 'ok',
				'message' => __( 'Payment page created', 'altapay' ),
				'page_id' => $pageID,
			)
		);
	}
	wp_die();
}

/**
 * Method for handling capture action and call back
 *
 * @return WP_Error
 */
function altapayCaptureCallback() {
	$utilMethods  = new Util\UtilMethods();
	$settings     = new Core\AltapaySettings();
	$orderID      = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
	$amount       = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : '';
	$subscription = false;
	if ( ! $orderID || ! $amount ) {
		wp_send_json_error( array( 'error' => 'error' ) );
	}

	// Load order
	$order = new WC_Order( $orderID );
	$txnID = $order->get_transaction_id();

	if ( class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $orderID, 'parent' ) || wcs_order_contains_subscription( $orderID, 'renewal' ) ) {
		$txnID        = get_post_meta( $orderID, '_agreement_id', true );
		$subscription = true;
	}

	if ( $txnID ) {

		$login = $settings->altapayApiLogin();
		if ( ! $login ) {
			wp_send_json_error( array( 'error' => 'Could not login to the Merchant API:' ) );
		} elseif ( is_wp_error( $login ) ) {
			wp_send_json_error( array( 'error' => wp_kses_post( $login->get_error_message() ) ) );
		}

		$postOrderLines = isset( $_POST['orderLines'] ) ? wp_unslash( $_POST['orderLines'] ) : '';

		$orderLines       = array();
		$selectedProducts = array(
			'itemList' => array(),
			'itemQty'  => array(),
		);
		if ( $postOrderLines ) {
			foreach ( $postOrderLines as $productData ) {
				if ( $productData[1]['value'] > 0 ) {
					$selectedProducts['itemList'][]                          = intval( $productData[0]['value'] );
					$selectedProducts['itemQty'][ $productData[0]['value'] ] = $productData[1]['value'];
				}
			}

			$orderLines = $utilMethods->createOrderLines( $order, $selectedProducts );
		}

		$response    = null;
		$rawResponse = null;
		try {
			if ( $subscription === true ) {
				$api = new ChargeSubscription( $settings->getAuth() );
			} else {
				$api = new CaptureReservation( $settings->getAuth() );
				$api->setOrderLines( $orderLines );
			}

			$api->setAmount( round( $amount, 2 ) );
			$api->setTransaction( $txnID );
			$response    = $api->call();
			$rawResponse = $api->getRawResponse();
		} catch ( InvalidArgumentException $e ) {
			error_log( 'InvalidArgumentException ' . $e->getMessage() );
			wp_send_json_error( array( 'error' => 'InvalidArgumentException: ' . $e->getMessage() ) );
		} catch ( ResponseHeaderException $e ) {
			error_log( 'ResponseHeaderException ' . $e->getMessage() );
			wp_send_json_error( array( 'error' => 'ResponseHeaderException: ' . $e->getMessage() ) );
		} catch ( \Exception $e ) {
			error_log( 'Exception ' . $e->getMessage() );
			wp_send_json_error( array( 'error' => 'Error: ' . $e->getMessage() ) );
		}

		if ( $response && $response->Result !== 'Success' ) {
			wp_send_json_error( array( 'error' => __( 'Could not capture reservation' ) ) );
		}

		$charge   = 0;
		$reserved = 0;
		$captured = 0;
		$refunded = 0;

		if ( $rawResponse ) {
			$body = $rawResponse->getBody();
			// Update comments if capture fail
			$xml = new SimpleXMLElement( $body );
			if ( (string) $xml->Body->Result === 'Error' || (string) $xml->Body->Result === 'Failed' ) {
				// log to history
				$order->add_order_note( __( 'Capture failed: ' . (string) $xml->Body->MerchantErrorMessage, 'Altapay' ) );
				wp_send_json_error( array( 'error' => (string) $xml->Body->MerchantErrorMessage ) );
			}

			$reserved = (float) $xml->Body->Transactions->Transaction->ReservedAmount;
			$captured = (float) $xml->Body->Transactions->Transaction->CapturedAmount;
			$refunded = (float) $xml->Body->Transactions->Transaction->RefundedAmount;
			$charge   = $reserved - $captured - $refunded;

			if ( $subscription === true ) {
				$xmlToJson          = wp_json_encode( $xml->Body->Transactions );
				$jsonToArray        = json_decode( $xmlToJson, true );
				$latest_transaction = $settings->getLatestTransaction( $jsonToArray['Transaction'], 'subscription_payment' );
				$transaction_id     = $jsonToArray['Transaction'][ $latest_transaction ]['TransactionId'];
				update_post_meta( $orderID, '_transaction_id', $transaction_id );
			}
		}

		if ( $charge <= 0 ) {
			$charge = 0.00;
		}

		foreach ( $selectedProducts['itemQty'] as $itemId => $qty ) {
			$args = array(
				'post_type'   => 'altapay_captures',
				'post_status' => 'captured',
				'post_parent' => $orderID,
			);

			$post_id = wp_insert_post( $args );

			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'qty_captured', $qty );
				update_post_meta( $post_id, 'item_id', $itemId );
			}
		}

		update_post_meta( $orderID, '_captured', true );
		$orderNote = __( 'Order captured: amount: ' . $amount, 'Altapay' );
		$order->add_order_note( $orderNote );
		$noteHtml = '<li class="note system-note"><div class="note_content"><p>' . $orderNote . '</p></div><p class="meta"><abbr class="exact-date">' . sprintf(
			__(
				'added on %1$s at %2$s',
				'woocommerce'
			),
			date_i18n( wc_date_format(), time() ),
			date_i18n( wc_time_format(), time() )
		) . '</abbr></p></li>';

		wp_send_json_success(
			array(
				'captured'   => $captured,
				'reserved'   => $reserved,
				'refunded'   => $refunded,
				'chargeable' => round( $charge, 2 ),
				'note'       => $noteHtml,
			)
		);
	}

	wp_die();
}

/**
 * Method for handling refund action and call back
 *
 * @return void
 */
function altapayRefundCallback() {
	$orderID = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
	$amount  = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0;

	$refund = altapayRefundPayment( $orderID, $amount, null, true );

	if ( $refund['success'] === true ) {
		wp_send_json_success( $refund );
	} else {
		$error = $refund['error'] ?? 'Error in the refund operation.';
		wp_send_json_error( array( 'error' => __( $error, 'altapay' ) ) );
	}

	wp_die();
}

/**
 * @param int        $orderID Order ID.
 * @param float|null $amount Refund amount.
 * @param string     $reason Refund reason.
 * @param boolean    $isAjax
 * @return array
 */
function altapayRefundPayment( $orderID, $amount, $reason, $isAjax ) {

	$utilMethods        = new Util\UtilMethods();
	$settings           = new Core\AltapaySettings();
	$orderLines         = array();
	$wcRefundOrderLines = array();

	if ( ! $orderID || ! $amount ) {
		return array( 'error' => 'Invalid order' );
	}

	// Load order
	$order = new WC_Order( $orderID );
	$txnID = $order->get_transaction_id();
	if ( ! $txnID ) {
		return array( 'error' => 'Invalid order' );
	}

	$login = $settings->altapayApiLogin();
	if ( ! $login ) {
		return array( 'error' => 'Could not login to the Merchant API:' );
	} elseif ( is_wp_error( $login ) ) {
		return array( 'error' => wp_kses_post( $login->get_error_message() ) );
	}

	$postOrderLines = isset( $_POST['orderLines'] ) ? wp_unslash( $_POST['orderLines'] ) : '';
	if ( $postOrderLines ) {
		$selectedProducts = array(
			'itemList' => array(),
			'itemQty'  => array(),
		);
		foreach ( $postOrderLines as $productData ) {
			if ( $productData[1]['value'] > 0 ) {
				$selectedProducts['itemList'][]                          = intval( $productData[0]['value'] );
				$selectedProducts['itemQty'][ $productData[0]['value'] ] = $productData[1]['value'];
			}
		}
		$orderLines         = $utilMethods->createOrderLines( $order, $selectedProducts );
		$wcRefundOrderLines = $utilMethods->createOrderLines( $order, $selectedProducts, true );
	}

	// Refund the amount OR release if a refund is not possible
	$releaseFlag = false;
	$refundFlag  = false;
	$auth        = $settings->getAuth();
	$error       = '';

	if ( get_post_meta( $orderID, '_captured', true ) || get_post_meta( $orderID, '_refunded', true ) || $order->get_remaining_refund_amount() > 0 ) {
		$api = new RefundCapturedReservation( $auth );
		$api->setAmount( round( $amount, 2 ) );
		$api->setOrderLines( $orderLines );
		$api->setTransaction( $txnID );

		try {
			$response = $api->call();
			if ( $response->Result === 'Success' ) {
				// Create refund in WooCommerce
				if ( $isAjax ) {
					// Restock the items
					$refundOperation = wc_create_refund(
						array(
							'amount'         => $amount,
							'reason'         => $reason,
							'order_id'       => $orderID,
							'line_items'     => $wcRefundOrderLines,
							'refund_payment' => false,
							'restock_items'  => true,
						)
					);

					if ( $refundOperation instanceof WP_Error ) {
						$order->add_order_note( __( $refundOperation->get_error_message(), 'altapay' ) );
					} else {
						$order->add_order_note( __( 'Refunded products have been re-added to the inventory', 'altapay' ) );
					}
				}
				update_post_meta( $orderID, '_refunded', true );
				$refundFlag = true;
			} else {
				$error = $response->MerchantErrorMessage;
			}
		} catch ( ResponseHeaderException $e ) {
			$error = 'Response header exception ' . $e->getMessage();
		} catch ( \Exception $e ) {
			$error = 'Response header exception ' . $e->getMessage();
		}
	} elseif ( $order->get_remaining_refund_amount() == 0 ) {

		try {
			$api = new ReleaseReservation( $auth );
			$api->setTransaction( $txnID );
			/** @var ReleaseReservationResponse $response */
			$response = $api->call();
			if ( $response->Result === 'Success' ) {
				$releaseFlag = true;
				$refundFlag  = true;
				update_post_meta( $orderID, '_released', true );
			} else {
				$error = $response->MerchantErrorMessage;
			}
		} catch ( ResponseHeaderException $e ) {
			$error = 'Response header exception ' . $e->getMessage();
		}
	}

	if ( ! $refundFlag ) {
		$order->add_order_note( __( 'Refund failed: ' . $error, 'altapay' ) );
		return array( 'error' => $error );
	} else {
		$reserved = 0;
		$captured = 0;
		$refunded = 0;
		$api      = new Payments( $auth );
		$api->setTransaction( $txnID );
		$payments = $api->call();

		if ( $payments ) {
			foreach ( $payments as $pay ) {
				$reserved += $pay->ReservedAmount;
				$captured += $pay->CapturedAmount;
				$refunded += $pay->RefundedAmount;
			}
		}

		$charge = $reserved - $captured - $refunded;
		if ( $charge <= 0 ) {
			$charge = 0.00;
		}

		if ( $releaseFlag ) {
			$order->add_order_note( __( 'Order released', 'altapay' ) );
			$orderNote = 'The order has been released';
		} else {
			$order->add_order_note( __( 'Order refunded: amount ' . $amount, 'altapay' ) );
			$orderNote = 'Order refunded: amount ' . $amount;
		}
		$noteHtml = '<li class="note system-note"><div class="note_content"><p>' . $orderNote . '</p></div><p class="meta"><abbr class="exact-date">' . sprintf(
			__(
				'added on %1$s at %2$s',
				'woocommerce'
			),
			date_i18n( wc_date_format(), time() ),
			date_i18n( wc_time_format(), time() )
		) . '</abbr></p></li>';

		return array(
			'captured'   => $captured,
			'reserved'   => $reserved,
			'refunded'   => $refunded,
			'chargeable' => round( $charge, 2 ),
			'note'       => $noteHtml,
			'success'    => true,
		);
	}
}

/**
 * Method for handling release action and call back
 *
 * @return void
 */
function altapayReleasePayment() {
	$orderID  = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
	$order    = new WC_Order( $orderID );
	$txnID    = $order->get_transaction_id();
	$settings = new Core\AltapaySettings();
	$captured = 0;
	$reserved = 0;
	$refunded = 0;

	$login = $settings->altapayApiLogin();
	if ( ! $login ) {
		wp_send_json_error( array( 'error' => 'Could not login to the Merchant API:' ) );
	} elseif ( is_wp_error( $login ) ) {
		wp_send_json_error( array( 'error' => wp_kses_post( $login->get_error_message() ) ) );
	}

	$auth = $settings->getAuth();
	$api  = new Payments( $auth );
	$api->setTransaction( $txnID );

	try {
		$payments = $api->call();
		foreach ( $payments as $pay ) {
			$reserved += $pay->ReservedAmount;
			$captured += $pay->CapturedAmount;
			$refunded += $pay->RefundedAmount;
		}

		if ( !$captured && !$refunded ) {
			$orderStatus = 'cancelled';
		} elseif ( $captured == $refunded && $refunded == $reserved || $refunded == $reserved ) {
			$orderStatus = 'refunded';
		} else {
			$orderStatus = 'processing';
		}

		$api = new ReleaseReservation( $auth );
		$api->setTransaction( $txnID );
		$response = $api->call();

		if ( $response->Result === 'Success' ) {
			$order->update_status( $orderStatus );
			if ( $orderStatus === 'cancelled' ) {
				update_post_meta( $orderID, '_released', true );
				$order->add_order_note( __( 'Order released: "The order has been released"', 'altapay' ) );
				wp_send_json_success( array( 'message' => 'Payment Released' ) );
			}
		} else {
			$order->add_order_note( __( 'Release failed: ' . $response->MerchantErrorMessage, 'altapay' ) );
			wp_send_json_error( array( 'error' => $response->MerchantErrorMessage ) );
		}
	} catch ( Exception $e ) {
		wp_send_json_error( array( 'error' => 'Could not login to the Merchant API: ' . $e->getMessage() ) );
	}
	wp_die();
}

/**
 * Perform functionality required during plugin activation
 */
function altapayPluginActivation() {
	Core\AltapayPluginInstall::createPluginTables();
}

register_activation_hook( __FILE__, 'altapayPluginActivation' );
add_action( 'add_meta_boxes', 'altapayAddMetaBoxes' );
add_action( 'wp_ajax_altapay_capture', 'altapayCaptureCallback' );
add_action( 'wp_ajax_altapay_refund', 'altapayRefundCallback' );
add_action( 'wp_ajax_altapay_release_payment', 'altapayReleasePayment' );
add_action( 'admin_footer', 'altapayActionJavascript' );
add_action( 'altapay_checkout_order_review', 'woocommerceOrderReview' );
add_action( 'wp_ajax_create_altapay_payment_page', 'createAltapayPaymentPageCallback' );
add_filter( 'template_include', 'altapay_page_template', 99 );
add_action( 'plugins_loaded', 'init_altapay_settings', 0 );
