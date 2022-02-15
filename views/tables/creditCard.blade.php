<?php
/**
 * AltaPay module for WooCommerce
 *
 * Copyright © 2020 AltaPay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <style>
        a {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <br>
    <form method="post">
        <table class="responsive-table bordered centered">
            <tbody>
                <tr style="font-weight: bold; border-collapse: collapse; padding: 15px;">
                    <td>Card type</td>
                    <td>Masked pan</td>
                    <td>Expires</td>
                    <td>Action</td>
                </tr>
            </tbody>
            @foreach($results as $result)
            <tr class="ap-orderlines-capture">
                <td> {{$result->cardBrand}} </td>
                <td> {{$result->creditCardNumber}} </td>
                <td> {{$result->cardExpiryDate}} </td>
                <td><a href="{{wc_get_endpoint_url( 'saved-credit-cards', '', get_permalink( wc_get_page_id( 'myaccount' ) ) )}}?delete_card={{$result->creditCardNumber}}">Delete</a></td>
            </tr>
            @endforeach
        </table>
    </form>
</body>
</html>
