<?php

use Altapay\Api\Others\Terminals;
use Altapay\Api\Test\TestAuthentication;
use GuzzleHttp\Exception\ClientException;
use Altapay\Authentication;

define('WP_USE_THEMES', false);
require('./wp-load.php');

// Settings
$apiUser = "~gatewayusername~";
$apiPass = "~gatewaypass~";
$url     = "~gatewayurl~";

try {
    $api      = new TestAuthentication(new Authentication($apiUser, $apiPass, $url));
    $response = $api->call();
    if (!$response) {
        echo "API credentials are incorrect";
        exit();
    }
} catch (ClientException $e) {
    echo "Error:" . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Error:" . $e->getMessage();
    exit();
}

$currency  = get_option('woocommerce_currency') ?: "DKK";
$terminals = array();

try {
    $api      = new Terminals(new Authentication($apiUser, $apiPass, $url));
    $response = $api->call();
    foreach ($response->Terminals as $terminal) {
        $terminals[] = $terminal->Title;
    }
} catch (ClientException $e) {
    echo "Error:" . $e->getMessage();
} catch (Exception $e) {
    echo "Error:" . $e->getMessage();
}

if (!$response) {
    echo "Terminal data not found";
    exit();
}

$paymentPage = get_page_by_path('altapay-payment-form');

if ($paymentPage) {
    $pageID = $paymentPage->ID;
} else {
    // Create payment page
    $page   = array(
        'post_type'    => 'page',
        'post_content' => '',
        'post_parent'  => 0,
        'post_author'  => 1,
        'post_status'  => 'publish',
        'post_title'   => 'AltaPay payment form',
    );
    $pageID = wp_insert_post($page);
}

update_option('altapay_payment_page', $pageID);
// Gateway Username
update_option('altapay_username', $apiUser);
// Gateway Password
update_option('altapay_password', $apiPass);
// Gateway URL
update_option('altapay_gateway_url', $url);

// Terminals Enabled
update_option('altapay_terminals_enabled', json_encode($terminals));

foreach ($terminals as $terminal) {
    $terminalSettings = array(
        "enabled"        => "yes",
        "title"          => $terminal,
        "description"    => "",
        "payment_action" => "authorize",
        "payment_icon"   => "default",
        "currency"       => $currency,
    );

    update_option('woocommerce_altapay_' . strtolower($terminal) . '_settings',
        apply_filters('woocommerce_settings_api_sanitized_fields_' . 'altapay_' . strtolower($terminal),
            $terminalSettings), 'yes');
}
