<?php
/**
 * Altapay module for Woocommerce
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'classes'.DIRECTORY_SEPARATOR .'util' . DIRECTORY_SEPARATOR . 'UtilMethods.php');
require_once(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'AltapayHelpers.php');

class WC_Gateway_Altapay_Test_Terminal extends WC_Payment_Gateway
{
    // see: https://docs.woocommerce.com/wc-apidocs/class-WC_Payment_Gateway.html
    //use AltapayMaster;

    public function __construct()
    {
        // Set default gateway values
        $this->id = strtolower('Altapay_Test_Terminal');
        $this->icon = ''; // Url to image
        $this->has_fields = false;
        $this->method_title = 'Altapay Test Terminal';
        $this->method_description = __('Adds Altapay Payment Gateway to use with WooCommerce', 'altapay');
        $this->supports = array(
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
        );

        $this->terminal = 'Test Terminal';
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->payment_action = $this->get_option('payment_action');
        $this->currency = $this->get_option('currency');
        $currency = explode(' ', 'Altapay_Test Terminal');
        $this->default_currency = end($currency);

        // Load form fields
        $this->init_form_fields();
        $this->init_settings();

        // Add actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page_altapay'));
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'checkAltapayResponse'));

        // Subscription actions
        add_action('woocommerce_scheduled_subscription_payment_altapay', array($this, 'scheduledSubscriptionPayment'),
            10, 2);

    }

    public function init_form_fields()
    {
        // Define form setting fields
        if('{tokenStatus}' == 'CreditCard'){
            $this->form_fields = include __DIR__."/../includes/AltapayFormFieldsToken.php";
        }
        else {
            $this->form_fields = include __DIR__."/../includes/AltapayFormFields.php";
        }
    }

    public function admin_options()
    {
        echo '<h3>Altapay Test Terminal</h3>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function receipt_page_altapay($order_id)
    {
        // Show text

        $return = $this->createPaymentRequest($order_id);
        if ($return instanceof WP_Error) {
            echo '<p>' . $return->get_error_message() . '</p>';
        } else {
            echo '<script type="text/javascript">window.location.href = "' . $return . '"</script>';
        }
    }

    public function createPaymentRequest($order_id)
    {
        global $wpdb;
        $utilMethods = new UtilMethods;
        $altapayHelpers = new AltapayHelpers;

        // Create form request etc.
        $api = $this->apiLogin();
        if ($api instanceof WP_Error) {
            $_SESSION['altapay_login_error'] = $api->get_error_message();
            echo '<p><b>' . __('Could not connect to Altapay!', 'altapay') . '</b></p>';
            return;
        }
        // Create payment request
        $order = new WC_Order($order_id);

        // TODO Get terminal form instance
        $terminal = $this->terminal;
        $amount = $order->get_total();
        $currency = $order->get_currency();
        $customerInfo = array(
            'billing_firstname' => $order->get_billing_first_name(),
            'billing_lastname' => $order->get_billing_last_name(),
            'billing_address' => $order->get_billing_address_1(),
            'billing_postal' => $order->get_billing_postcode(),
            'billing_city' => $order->get_billing_city(),
            'billing_region' => $order->get_billing_state(),
            'billing_country' => $order->get_billing_country(),
            'email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'shipping_firstname' => $order->get_shipping_first_name(),
            'shipping_lastname' => $order->get_shipping_last_name(),
            'shipping_address' => $order->get_shipping_address_1(),
            'shipping_postal' => $order->get_shipping_postcode(),
            'shipping_city' => $order->get_shipping_city(),
            'shipping_region' => $order->get_shipping_state(),
            'shipping_country' => $order->get_shipping_country(),
        );

        $customerInfo = $altapayHelpers->setShippingAddress($customerInfo);
        if ($customerInfo instanceof WP_Error) {
            return $customerInfo; // Shipping country is missing
        }
        $cookie = isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '';
        $language = 'en';
        $languages = array(
            'da_DK' => 'da',
            'sv_SE' => 'sv',
            'nn_NO' => 'no',
            'no_NO' => 'no',
            'nb_NO' => 'no',
            'de_DE' => 'de',
            'cs_CZ' => 'cs',
            'fi_FI' => 'fi',
            'fr_FR' => 'fr',
            'lt' => 'lt',
            'nl_NL' => 'nl',
            'pl_PL' => 'pl',
            'et' => 'et',
            'ee' => 'et',
            'en_US' => 'en',
            'it' => 'it'
        );
        if (array_key_exists(get_locale(), $languages)) {
            $language = $languages[get_locale()];
        }

        // Get chosen page from altapay settings
        $form_page_id = esc_attr(get_option('altapay_payment_page'));
        $config = array(
            'callback_form' => get_page_link($form_page_id),
            'callback_ok' => add_query_arg(array('type' => 'ok', 'wc-api' => 'WC_Gateway_' . $this->id),
                $this->get_return_url($order)),
            'callback_fail' => add_query_arg(array('type' => 'fail', 'wc-api' => 'WC_Gateway_' . $this->id),
                $this->get_return_url($order)),
            'callback_open' => add_query_arg(array('type' => 'open', 'wc-api' => 'WC_Gateway_' . $this->id),
                $this->get_return_url($order)),
            'callback_notification' => add_query_arg(array(
                'type' => 'notification',
                'wc-api' => 'WC_Gateway_' . $this->id
            ), $this->get_return_url($order)),
        );

        // Make these as settings
        $payment_type = 'payment';
        if ($this->payment_action == 'authorize_capture') {
            $payment_type = 'paymentAndCapture';
        }

        // Check if WooCommerce subscriptions is enabled
        if (class_exists('WC_Subscriptions_Order')) {
            // Check if cart containt subscription product
            if (WC_Subscriptions_Order::order_contains_subscription($order_id)) {
                if ($this->payment_action == 'authorize_capture') {
                    $payment_type = 'subscriptionAndCharge';
                } else {
                    $payment_type = 'subscriptionAndReserve';
                }
            }
        }


        $transactionInfo = $altapayHelpers->transactionInfo();
        // Add orderlines to Altapay request
        $orderLines = $utilMethods->createOrderLines($order);
        if ($orderLines instanceof WP_Error) {
            return $orderLines; // Some error occurred
        }

        try {
            $response = $api->createPaymentRequest($terminal, $order_id, $amount, $currency, $payment_type,
                $customerInfo, $cookie, $language, $config, $transactionInfo, $orderLines, false, 'zoA/bu8ysCPTHp+2hwmzd1Pkr54ZrKtpIJkgfi5QbeyzfXpGDcxgIIB7rqIaE8FRLWV16jc+TL/Dx0ig4KWgjg==+1');
            $responseError = $response->getErrorMessage();

            if (!empty($responseError)) {
                $errorMessage = new WP_Error('ResponseError', $responseError);
                return $errorMessage;
            }

            echo '<p>' . __('You are now going to be redirected to Altapay Payment Gateway', 'altapay') . '</p>';
            $redirectURL = $response->getRedirectURL();
            return $redirectURL;
        } catch (Exception $e) {
            error_log('Could not create the payment request: ' . $e->getMessage());
            $order->add_order_note(__('Could not create the payment request: ' . $e->getMessage(), 'Altapay'));
            return new WP_Error('error', 'Could not create the payment request');
        }
    }


    public function checkAltapayResponse()
    {
        // Check if callback is altapay and the allowed IP
        if ($_GET['wc-api'] == 'WC_Gateway_' . $this->id) {
            global $woocommerce;
            $postdata = $_POST;

            $order_id = sanitize_text_field($postdata['shop_orderid']);
            $order = new WC_Order($order_id);
            $txnid = sanitize_text_field($postdata['transaction_id']);
            $cardno = sanitize_text_field($postdata['masked_credit_card']);
            //$amount = sanitize_text_field($postdata['amount']);
            $credtoken = sanitize_text_field($postdata['credit_card_token']);
            $merchant_error_message = '';
            $error_message = '';
            if (array_key_exists('merchant_error_message', $postdata)) {
                $merchant_error_message = sanitize_text_field($postdata['merchant_error_message']);
            }
            if (array_key_exists('error_message', $postdata)) {
                $error_message = sanitize_text_field($postdata['error_message']);
            }
            $payment_status = sanitize_text_field($postdata['payment_status']);
            $status = sanitize_text_field($postdata['status']);

            // TODO Clean up

            // If order already on-hold
            if ($order->has_status('on-hold')) {

                if ($status == 'succeeded') {

                    $order->add_order_note(__('Notification completed', 'Altapay'));
                    $order->payment_complete();

                    update_post_meta($order_id, '_transaction_id', $txnid);
                    update_post_meta($order_id, '_cardno', $cardno);
                    update_post_meta($order_id, '_credit_card_token', $credtoken);

                } else {
                    if ($status == 'error' || $status == 'failed') {
                        $order->update_status('failed', 'Payment failed: ' . $error_message);
                        $order->add_order_note(__('Payment failed: ' . $error_message . ' Merchant error: ' . $merchant_error_message,
                            'Altapay'));
                    }
                }

                exit;
            }

            if ($status == 'open') {
                $order->update_status('on-hold', 'The payment is pending an update from the payment provider.');

                $redirect = $this->get_return_url($order);
                wp_redirect($redirect);
                exit;
            }

            if ($payment_status == 'released') {
                $order->add_order_note(__('Payment failed: payment released', 'Altapay'));
                wc_add_notice(__('Payment error:', 'altapay') . ' Payment released', 'error');
                wp_redirect(wc_get_cart_url());
                exit;
            }

            if (array_key_exists('cancel_order', $_GET)) {
                $order->add_order_note(__('Payment failed: ' . $error_message . ' Merchant error: ' . $merchant_error_message,
                    'Altapay'));
                wc_add_notice(__('Payment error:', 'altapay') . ' ' . $error_message, 'error');
                wp_redirect(wc_get_cart_url());
                exit;
            }

            // Make some validation
            if ($error_message || $merchant_error_message) {
                $order->add_order_note(__('Payment failed: ' . $error_message . ' Merchant error: ' . $merchant_error_message,
                    'Altapay'));
                wc_add_notice(__('Payment error:', 'altapay') . ' ' . $error_message, 'error');
                wp_redirect(wc_get_cart_url());
                exit;
            }

            if ($order->has_status('pending') && $status == 'succeeded') {
                // Payment completed
                $order->add_order_note(__('Callback completed', 'Altapay'));
                $order->payment_complete();

                update_post_meta($order_id, '_transaction_id', $txnid);
                update_post_meta($order_id, '_cardno', $cardno);
                update_post_meta($order_id, '_credit_card_token', $credtoken);
            }

            // Redirect to Accept page
            $redirect = $this->get_return_url($order);
            wp_redirect($redirect);
            exit;
        }
    }

}
