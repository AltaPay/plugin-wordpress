<!--
  ~ Altapay module for Woocommerce
  ~
  ~ Copyright © 2020 Altapay. All rights reserved.
  ~ For the full copyright and license information, please view the LICENSE
  ~ file that was distributed with this source code.
  -->

<form method="post" action="options.php">
    @php
        settings_fields('altapay-settings-group');
        do_settings_sections('altapay-settings-group');
    @endphp
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Gateway URL', 'altapay') ?></th>
            <td><input class="input-text regular-input" type="text" placeholder="{{__('Enter gateway url','altapay')}}" name="altapay_gateway_url"
                       value="{{$gatewayURL}}"/>
               <i><p style="font-size: 10px;">{{__('e.g. https://www.testgateway.altapaysecure.com/', 'altapay')}}</p></i>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo __('API username', 'altapay') ?></th>
            <td><input class="input-text regular-input" type="text" placeholder="{{__('Enter API username','altapay')}}" name="altapay_username"
                       value="{{$username}}"/><br><br>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php echo __('API password', 'altapay') ?></th>
            <td><input class="input-text regular-input" type="password" placeholder="{{__('Enter API password','altapay')}}" name="altapay_password"
                       value="{{$password}}"/><br><br>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo __('Payment page', 'altapay') ?></th>
            <td>
                @php // Validate if payment page exists by looping trough the pages
                    $pages = get_pages();
                    foreach ($pages as $page) {
                        if ($page->post_name == 'altapay-payment-form') {
                            $exists = true;
                            $pageTitle = $page->post_title;
                            $pageID = $page->ID;
                            update_option('altapay_payment_page', $pageID);
                        }
                    }
                @endphp
                @if (!$exists)
                    <input type="button" id="create_altapay_payment_page" style="color:white; background-color:#006064;" name="create_page"
                           value="Create Page" class="button button-primary btn-lg"/>
                    <i><p style="font-size: 10px;" id="payment-page-msg">{{__('Payment page does not exist, create a new one', 'altapay')}}</p></i>
                    <span id="payment-page-msg"></span>
                    <input type="hidden" name="altapay_payment_page"  id="altapay_payment_page" value="">
                @else
                    <input type="hidden" name="altapay_payment_page"
                     value="{{$paymentPage}}">{{$pageID}}: {{$pageTitle}}
                @endif

            </td>
        </tr>
        @if ($terminals)
            <tr valign="top">
                <th scope="row" colspan="2">
                    <div style="background: #006064; height: 30px;">
                    <h2 style="color:white; line-height: 30px; padding-left: 1%;"><?php echo __('Terminals', 'altapay') ?></h2>
                    </div>
                </th>
            </tr>
            @foreach ($terminals as $terminal)
                <tr valign="top">
                    <th scope="row">{{$terminal->name}}</th>
                    <td><input type="checkbox" name="altapay_terminals_enabled[]"
                               value="{{$terminal->key}}"
                               @if (in_array($terminal->key, $enabledTerminals)) checked="checked"/> @endif
                    </td>
                </tr>
            @endforeach
            <tr>
                <td>
                    <a href="admin.php?page=wc-settings&amp;tab=checkout"><?= __('Go to WooCommerce payment methods',
                            'altapay') ?></a>
                </td>
            </tr>
        @endif

    </table>
    <input type="submit" class="button" style="color:white; background-color:#006064;" value="Save changes"/>
</form>