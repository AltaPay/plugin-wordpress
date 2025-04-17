<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
?>

<table class="w-100 center" cellspacing="0">
    <thead>
    <tr>
        <th width="28%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Product name', 'altapay' ); ?></th>
        <th width="12%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Price with tax', 'altapay' ); ?></th>
        <th width="12%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Price without tax', 'altapay' ); ?></th>
        <th width="12%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Ordered', 'altapay' ); ?></th>
        <th width="12%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Discount Percent', 'altapay' ); ?></th>
        <th width="12%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Quantity', 'altapay' ); ?></th>
        <th width="12%" class="fw6 bb b--black-20 tl pb3 pr3 bg-white"><?php esc_html_e( 'Total amount', 'altapay' ); ?></th>
    </tr>
    </thead>
    <tbody>

    @foreach($order->get_items() as $item)

        @if($item->get_total() == 0)
            @continue;
        @endif
        @php
            $productID = $item->get_id();
		    $product = $item->get_product();
            $qty = $item->get_quantity();
            $refunded = abs($order->get_qty_refunded_for_item( $productID ));
            $refundableQty = $qty - $refunded;
			$subtotal = $item->get_subtotal();
			$total = $item->get_total();

            $discountPercent = round((($subtotal - $total) / $subtotal) * 100, 2);
            $productUnitPriceWithoutTax = round(($total / $qty), 2);
            $productUnitPriceWithTax = round(($total / $qty) + ($item->get_total_tax() / $qty), 2);
            $totalIncTax = round($total + $item->get_total_tax(), 2);
        @endphp
        @include('tables.order-line', [
            'itemId' => $productID,
            'itemName' => $item->get_name(),
            'priceWithTax' => $productUnitPriceWithTax,
            'priceWithoutTax' => $productUnitPriceWithoutTax,
            'qty' => $qty,
            'discountPercent' => $discountPercent,
            'availableQty' => $refundableQty,
            'currency' => $order->get_currency(),
            'totalIncTax' => $totalIncTax,
            'type' => 'refund',
        ])
    @endforeach

    @if ($order->get_shipping_total() <> 0 || $order->get_shipping_tax() <> 0)
        @php
            $order_shipping_methods = $order->get_shipping_methods();
            $discountPercentage = 0;
            $totalIncTax = (float)number_format($order->get_shipping_total() + $order->get_shipping_tax(), 2, '.', '');
            $excTax =$order->get_shipping_total();
        @endphp

        @foreach ($order_shipping_methods as $ordershipping_key => $ordershippingmethods)
            @php($shipping_id = $ordershippingmethods['method_id'])
        @endforeach
        @php
            $remaining_refund_amount = $order->get_remaining_refund_amount();
        @endphp
        @include('tables.order-line', [
            'itemId' => $shipping_id,
            'itemName' => $order->get_shipping_method(),
            'priceWithTax' => $totalIncTax,
            'priceWithoutTax' => $excTax,
            'qty' => 1,
            'discountPercent' => $discountPercentage,
            'availableQty' => $remaining_refund_amount == 0 ? 0 : 1,
            'currency' => $order->get_currency(),
            'totalIncTax' => $totalIncTax,
            'type' => 'refund',
        ])
    @endif
    @php
        $fees = $order->get_fees();
    @endphp
    @foreach( $fees as $fee )
        @php
            $surchargeAmount = (float) $fee->get_total();
        @endphp
        @if( $fee->get_name() === 'Surcharge' )
            @include('tables.order-line', [
                'itemId' => $fee->get_id(),
                'itemName' => $fee->get_name(),
                'priceWithTax' => $surchargeAmount,
                'priceWithoutTax' => $surchargeAmount,
                'qty' => 1,
                'discountPercent' => 0,
                'availableQty' => 1,
                'currency' => $order->get_currency(),
                'totalIncTax' => $surchargeAmount,
                'type' => 'refund',
            ])
        @endif
    @endforeach
    </tbody>
</table>