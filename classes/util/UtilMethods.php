<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Altapay\Classes\Util;

use WC_Coupon;
use WP_Error;
use Altapay\Request\OrderLine;

class UtilMethods {

	/**
	 * Generates order lines from an order or from an order refund.
	 *
	 * @param WC_Order|WC_Order_Refund $order
	 * @param array                    $products
	 * @param bool                     $wcRefund
	 * @return array  list of order lines
	 */
	public function createOrderLines( $order, $products = array(), $wcRefund = false, $isSubscription = false ) {
		$orderlineDetails         = array();
		$itemsToCapture           = array();
		$couponDiscountPercentage = 0; // set initial coupon discount to 0
		$cartItems                = $order->get_items(); // get cart items

		// If capture request is triggered
		if ( $products ) {
			foreach ( $cartItems as $key => $value ) {
				if ( in_array( intval( $key ), $products['itemList'], true ) ) {
					$itemsToCapture[ $key ] = $value;
				}
			}
			$cartItems = $itemsToCapture;
		}

		// if cart is empty
		if ( ! $cartItems ) {
			return new WP_Error( 'error', __( 'There are no items in the cart ', 'altapay' ) );
		}

		$i = 0;

		// generate order lines product by product
		foreach ( $cartItems as $key => $item ) {

			if ( $item->get_total() == 0 ) {
				continue;
			}

			$appliedCouponItems = $order->get_items( 'coupon' ); // get items with coupon discount
			if ( $appliedCouponItems ) {
				$couponDiscountPercentage = $this->getCouponDiscount( $appliedCouponItems, $item );
			}
			// get product details for each order line
			$productDetails = $this->getProductDetails( $item, $couponDiscountPercentage, ( ( $i == 0 ) ? $isSubscription : 0 ) );

			if ( $wcRefund ) {
				$orderlineDetails[ $key ] = array(
					'qty'          => $products['itemQty'][ $key ],
					'refund_total' => ( $item['line_total'] / $item->get_quantity() ) * $products['itemQty'][ $key ],
					'refund_tax'   => ( $item->get_total_tax() / $item->get_quantity() ) * $products['itemQty'][ $key ],
				);
			} else {
				$orderlineDetails[] = $productDetails['product'];
			}

			// check if compensation exists to bind it in orderline details
			if ( ! $wcRefund && isset( $productDetails['compensation'] ) ) {
				$orderlineDetails [] = $productDetails['compensation'];
			}

			$i++;
		}
		// get the shipping Details
		$shippingDetails = $this->getShippingDetails(
			$order,
			$products,
			$wcRefund
		);

		if ( $shippingDetails ) {
			$shippingDetails     = reset( $shippingDetails );
			$orderlineDetails [] = $shippingDetails;
		}

		return $orderlineDetails;
	}

	/**
	 * Returns the total of multiple discounts applied on each order line
	 *
	 * @param array $appliedCouponItems
	 * @param  array $orderline
	 * @return float returns the float value of percentage discounts applied using coupon codes
	 */
	public function getCouponDiscount( $appliedCouponItems, $orderline ) {
		$discountPercentageWholeCart           = 0;
		$discountPercentageOnParticularProduct = 0;
		$productsWithCoupon                    = array();

		if ( $appliedCouponItems ) {
			foreach ( $appliedCouponItems as $item_id => $item ) {
				// Retrieving the coupon ID reference
				$couponPostObj = get_page_by_title( $item->get_name(), OBJECT, 'shop_coupon' );
				$couponID      = $couponPostObj->ID;
				// Get an instance of WC_Coupon object (necessary to use WC_Coupon methods)
				$coupon         = new WC_Coupon( $couponID );
				$couponType     = $coupon->discount_type;
				$appliedCoupons = reset( $coupon );
				// Filtering with your coupon custom types
				if ( $couponType === 'percent' && empty( $appliedCoupons['product_ids'] ) ) {
					// Get the Coupon discount amounts in the order
					$orderDiscountAmount    = wc_get_order_item_meta( $item_id, 'discount_amount', true );
					$orderDiscountTaxAmount = wc_get_order_item_meta( $item_id, 'discount_amount_tax', true );
					// This calculation will assist in scenario of discount coupons on entire cart
					$totalCouponDiscountAmount = $orderDiscountAmount + $orderDiscountTaxAmount;
					// Or get the coupon amount object
					$discountPercentageWholeCart += $coupon->amount;
				} elseif ( $couponType === 'percent' && ! empty( $appliedCoupons['product_ids'] ) ) {
					$discountPercentageOnParticularProduct = $coupon->amount;
					$productsWithCoupon                    = array_values( $appliedCoupons['product_ids'] );
				}
			}
		}
		if ( in_array( $orderline['product_id'], $productsWithCoupon ) || in_array(
			$orderline['variation_id'],
			$productsWithCoupon
		) ) {
			$discountPercentage = $discountPercentageWholeCart + $discountPercentageOnParticularProduct;
		} else {
			$discountPercentage = $discountPercentageWholeCart;
		}

		return $discountPercentage;
	}

	/**
	 * Returns product Details based on product type and tax configuration settings
	 *
	 * @param object[] $item
	 * @param float    $couponDiscountPercentage
	 * @return array
	 */
	private function getProductDetails( $item, $couponDiscountPercentage, $isSubscription = false ) {
		$discountPercentage  = 0; // set discount Percent to 0 by default
		$productCartId       = $item->get_id(); // product Cart ID number
		$singleProduct       = wc_get_product( $item['product_id'] ); // Details of each product
		$productQuantity     = $item['qty']; // get ordered number of quantity for each orderline
		$productRegularPrice = $singleProduct->get_regular_price();
		$productSalePrice    = $singleProduct->get_sale_price();
		$productData         = array();

		// Get and set tax rate using order line
		$orderlineTax = $item['subtotal'] > 0 ? array_sum( $item['taxes']['subtotal'] ) / $item['subtotal'] : 0;
		$taxRate      = $orderlineTax;

		// CMS total
		$totalCMS = $item->get_total() + $item->get_total_tax();

		// set product ID based on the sku provided
		if ( $singleProduct->get_sku() ) {
			$productId = $productCartId . '-' . $singleProduct->get_sku();
		} else {
			$productId = $productCartId;
		}

		// get and set product details in case of variable product
		if ( $singleProduct->get_type() === 'variable' ) {
			$variablePrices      = $singleProduct->get_variation_prices(); // get all the variation prices i.e regular and sale
			$variationID         = $item['variation_id']; // get variation id of ordered orderline
			$productRegularPrice = $variablePrices['regular_price'][ $variationID ]; // get regular price from variation prices array
			$productSalePrice    = $variablePrices['sale_price'][ $variationID ]; // get regular price from variation prices array
		}

		// calculate discount if catalogue rule is applied on order line i.e. product sale price is set
		if ( $singleProduct->is_on_sale() ) {
			$productDiscountAmount = $productRegularPrice - $productSalePrice; // calculate discount amount
			// convert discount amount into percentage
			$discountPercentage = round( ( $productDiscountAmount / $productRegularPrice ) * 100, 2 );
		}

		if ( $couponDiscountPercentage > 0 ) {
			$discountPercentage = $couponDiscountPercentage;
		}

		if ( wc_prices_include_tax() ) {
			$taxRate      = 1 + $orderlineTax;
			$productPrice = round( $productRegularPrice / $taxRate, 2 );
			$taxAmount    = $productRegularPrice - $productPrice;
		} else {
			$productPrice = $productRegularPrice;
			$taxAmount    = $productPrice * $taxRate;
		}

		// calculate total generated from order lines generated after calculation
		$totalOrderlines = ( ( $productPrice + $taxAmount ) - ( ( $productPrice + $taxAmount ) * ( $discountPercentage / 100 ) ) ) * $productQuantity;
		// calculate compensation amount between total generated from woocommerce and total generated from orderlines
		$compensationAmount = round( ( $totalCMS - $totalOrderlines ), 3 );
		// generate compensation amount order line using product id and amount
		if ( $compensationAmount > 0 || $compensationAmount < 0 ) {
			$productData['compensation'] = $this->compensationAmountOrderline( $productId, $compensationAmount );
		}
		// generate line date with all the calculated parameters
		$orderLine = new OrderLine(
			$item['name'],
			$productId,
			$productQuantity,
			round( $productPrice, 2 )
		);

		$orderLine->discount   = round( $discountPercentage, 2 );
		$orderLine->taxAmount  = round( $taxAmount * $productQuantity, 2 );
		$orderLine->taxPercent = $productPrice > 0 ? round( ( $taxAmount / $productPrice ) * 100, 2 ) : 0;
		$orderLine->productUrl = get_permalink( $singleProduct->get_id() );
		$orderLine->imageUrl   = wp_get_attachment_url( get_post_thumbnail_id( $singleProduct->get_id() ) );
		$orderLine->unitCode   = $productQuantity > 1 ? 'units' : 'unit';
		$goodsType             = ( $isSubscription ) ? 'subscription_model' : 'item';
		$orderLine->setGoodsType( $goodsType );
		$productData['product'] = $orderLine;

		return $productData;
	}

	/**
	 * Returns compensation amount orderline to bind within payment request
	 *
	 * @param int   $productId
	 * @param float $compensationAmount
	 *
	 * @return OrderLine
	 */
	public function compensationAmountOrderline( $productId, $compensationAmount ) {
		// Generate compensation amount orderline for payment, capture and refund requests
		$orderLine             = new OrderLine(
			'Compensation',
			'comp-' . $productId,
			1,
			$compensationAmount
		);
		$orderLine->taxAmount  = 0.00;
		$orderLine->taxPercent = 0.00;
		$orderLine->unitCode   = 'unit';
		$orderLine->discount   = 0.00;
		$orderLine->setGoodsType( 'handling' );

		return $orderLine;
	}

	/**
	 * Returns the shipping method orderline for order
	 *
	 * @param WC_Order $order
	 * @param array    $products
	 * @param bool     $wcRefund
	 * @return array|bool
	 */
	private function getShippingDetails( $order, $products, $wcRefund ) {
		// Get the shipping method
		$orderShippingMethods = $order->get_shipping_methods();
		$shippingID           = 'NaN';
		$shippingDetails      = array();

		$orderShippingKey = 0;
		foreach ( $orderShippingMethods as $orderShippingKey => $orderShippingMethods ) {
			$shippingID = $orderShippingMethods['method_id'];
		}
		// In a refund it's possible to have order_shipping == 0 and order_shipping_tax != 0 at the same time
		if ( $order->get_shipping_total() != 0 || $order->get_shipping_tax() != 0 ) {
			if ( $products ) {
				if ( ! in_array( $shippingID, $products['itemList'] ) ) {
					return false;
				}
			}
			// getting shipping total and tax applied on it
			$totalShipping    = $order->get_shipping_total();
			$totalShippingTax = $order->get_shipping_tax();

			// This will trigger in case a refund action is performed
			if ( $wcRefund ) {
				$shippingDetails[ $orderShippingKey ] = array(
					'qty'          => 1,
					'refund_total' => wc_format_decimal( $totalShipping ),
					'refund_tax'   => wc_format_decimal( $totalShippingTax ),
				);
			} else {
				$orderLine             = new OrderLine(
					$order->get_shipping_method(),
					$shippingID,
					1,
					round( $totalShipping, 2 )
				);
				$orderLine->taxAmount  = round( $totalShippingTax, 2 );
				$orderLine->taxPercent = round( ( $totalShippingTax / $totalShipping ) * 100, 2 );
				$goodsType             = 'shipment';
				$orderLine->setGoodsType( $goodsType );
				$shippingDetails[] = $orderLine;
			}
		}
		return $shippingDetails;
	}
}
