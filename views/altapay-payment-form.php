<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The template for displaying AltaPay's payment form
 *
 * @package Altapay
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header();
?>
<style>
	.pensio_payment_form_cvc_cell img {
		max-width: 60px;
	}
	.pensio_payment_form_row {
		margin-bottom: 15px;
	}
	.pensio_payment_form_input_cell img {
		display: inline-block;
		margin-left: 5px;
		vertical-align: middle;
	}
	.altapay-page-wrapper {
		display: flex;
		width: 100%;
	}
	.altapay-page-wrapper .altapay-payment-form-cnt, .altapay-page-wrapper .altapay-order-details {
		padding: 15px;
	}
	.altapay-page-wrapper .altapay-payment-form-cnt {
		padding-top: 50px;
	}
	input#creditCardNumberInput, input#cardholderNameInput {
		width: 100%;
		max-width: 300px;
	}
	input#cvcInput {
		min-width: 100px;
		max-width: 140px;
	}
	select#emonth, select#eyear {
		max-width: 100px;
	}
	.site-main {
		width: 100%;
	}
	@media screen and (min-width:769px){
		.altapay-page-wrapper .altapay-payment-form-cnt, .altapay-page-wrapper .altapay-order-details {
			flex: 1;
		}
	}
</style>
<main id="main" class="site-main" role="main">
	<div class="container">
		<div class="row">
			<div class="altapay-page-wrapper">
				<div class="altapay-payment-form-cnt">
					<form id="PensioPaymentForm"></form>
				</div>
				<div class="altapay-order-details">
				<?php
					$order_id = isset( $_POST['shop_orderid'] ) ? wp_unslash( $_POST['shop_orderid'] ) : 0;
					woocommerce_order_details_table( $order_id );
				?>
				</div>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
