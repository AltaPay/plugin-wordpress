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

			if ( isset( $_REQUEST['export_reconciliation_data'] ) ) {

			}
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
}
