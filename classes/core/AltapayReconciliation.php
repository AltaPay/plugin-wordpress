<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Altapay\Classes\Core;

class AltapayReconciliation {

	/**
	 * Register required hooks
	 *
	 * @return void
	 */
	public function registerHooks() {
		add_action( 'manage_posts_extra_tablenav', array( $this, 'reconciliation_data_export_button' ), 20, 1 );
		add_action( 'woocommerce_after_register_post_type', array( $this, 'exportReconciliationCSV' ) );
	}

	/**
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 *
	 * @return void
	 */
	public function reconciliation_data_export_button( $which ) {
		global $typenow;

		if ( 'shop_order' === $typenow && 'top' === $which ) {
			?>
			<div class="alignleft actions altapay-export-reconciliation-data">
				<button type="submit" name="export_reconciliation_data" class="button button-primary" value="1">
					<?php echo __( 'Export Reconciliation Data', 'altapay' ); ?>
				</button>
			</div>
			<?php
		}
	}

	/**
	 * Save the reconciliation identifier details.
	 *
	 * @param int    $orderId
	 * @param string $transactionId
	 * @param string $identifier
	 * @param string $type
	 *
	 * @return void
	 */
	public function saveReconciliationIdentifier( $orderId, $transactionId, $identifier, $type ) {
		global $wpdb;

		$record_exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT id from {$wpdb->prefix}altapayReconciliationIdentifiers where orderId = %d and identifier = %s", $orderId, $identifier )
		);

		if ( $record_exists ) {
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'altapayReconciliationIdentifiers',
			array(
				'orderId'         => $orderId,
				'time'            => current_time( 'mysql' ),
				'transactionId'   => $transactionId,
				'identifier'      => $identifier,
				'transactionType' => $type,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

	}

	/**
	 * @param int $orderId
	 *
	 * @return array|null
	 */
	public function getReconciliationData( $orderId ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}altapayReconciliationIdentifiers WHERE orderId = %d", $orderId ), ARRAY_A );
	}

	/**
	 * Export Reconciliation CSV file.
	 *
	 * @return void
	 */
	public function exportReconciliationCSV() {
		if ( isset( $_REQUEST['export_reconciliation_data'] ) ) {
			global $wpdb;

			$output    = '';
			$file_name = 'reconciliation_data.csv';

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $file_name );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$reconciliation_data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}altapayReconciliationIdentifiers", ARRAY_A );

			$output  = $output . 'Order ID,Date Created,Order Total,Currency,Transaction ID,Reconciliation Identifier,Type,Payment Method,Order Status';
			$output .= "\n";

			if ( ! empty( $reconciliation_data ) ) {
				foreach ( $reconciliation_data as $data ) {
					$order = wc_get_order( $data['orderId'] );

					if ( $order ) {

						$dateLocalised = ! is_null( $order->get_date_created() ) ? $order->get_date_created()->getOffsetTimestamp() : '';
						$createdDate   = esc_attr( date_i18n( 'Y-m-d', $dateLocalised ) );
						$paymentMethod = $order->get_payment_method_title();
						$total         = $order->get_total();
						$status        = $order->get_status();
						$currency      = $order->get_currency();
						$transactionId = $order->get_transaction_id();

						$output .= $data['orderId'] . ',' . $createdDate . ',' . $total . ',' . $currency . ',' . $transactionId . ',' . $data['identifier'] . ',' . $data['transactionType'] . ',' . $paymentMethod . ',' . $status;
						$output .= "\n";
					}
				}
			}

			echo $output;
			exit;
		}
	}
}
