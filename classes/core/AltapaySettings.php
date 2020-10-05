<?php
/**
 * Altapay module for Woocommerce
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'altapay' . DIRECTORY_SEPARATOR . 'altapay-php-sdk' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'AltapayMerchantAPI.class.php');
require_once(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'util' . DIRECTORY_SEPARATOR . 'UtilMethods.php');
require_once(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'traits.php');
require_once(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'AltapayHelpers.php');

class AltapaySettings
{
    use AltapayMaster;
    private $plugin_options_key = 'altapay-settings';

    /**
     * AltapaySettings constructor.
     */
    public function __construct()
    {
        // Load localization files
        add_action('init', array($this, 'altapayLocalizationInit'));
        // Add admin menu
        add_action('admin_menu', array($this, 'altapaySettingsMenu'), 60);
        // Register settings
        add_action('admin_init', array($this, 'altapayRegisterSettings'));
        // Add settings link on plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'addActionLinks'));
        // Order completed interceptor:
        add_action('woocommerce_order_status_completed', array($this, 'altapayOrderStatusCompleted'));

        add_action('admin_notices', array($this, 'loginError'));
        add_action('admin_notices', array($this, 'captureFailed'));
        add_action('admin_notices', array($this, 'captureWarning'));
    }

    /**
     * @param $orderID
     * @throws AltapayMerchantAPIException
     */
    public function altapayOrderStatusCompleted($orderID)
    {
        $this->startSession();
        // Load order
        $order = new WC_Order($orderID);
        $txnID = $order->get_transaction_id();

        if (!$txnID) {
            return;
        }

        $api = $this->apiLogin();
        if ($api instanceof WP_Error) {
            $_SESSION['altapay_login_error'] = $api->get_error_message();
            echo '<p><b>' . __('Could not connect to Altapay!', 'altapay') . '</b></p>';
            return;
        }

        $payment = $api->getPayment($txnID);
        if (!$payment) {
            return;
        }

        $payments = $payment->getPayments();
        $pay = $payments[0];

        if ($pay->getCapturedAmount() == 0) { // Order wasn't captured and must be captured now
            $amount = $pay->getReservedAmount(); // Amount to capture
            $salesTax = $order->get_total_tax();
            $orderLines = array(array());
            $captureResult = $api->captureReservation($txnID, $amount, $orderLines, $salesTax);

            if (!$captureResult->wasSuccessful()) {
                $order->add_order_note(__('Capture failed: ' . $captureResult->getMerchantErrorMessage(),
                    'Altapay')); // log to history
                $this->saveCaptureFailedMessage('Capture failed for order ' . $orderID . ': ' . $captureResult->getMerchantErrorMessage());
                return;
            } else {
                update_post_meta($orderID, '_captured', true);
                $order->add_order_note(__('Order captured: amount: ' . $amount, 'Altapay'));
            }
        } else {
            $this->saveCaptureWarning('This order was already fully or partially captured: ' . $orderID);
        }
    }


    /**
     * starts the session
     */
    public function startSession()
    {
        if (session_id() === '') {
            session_start();
        }
    }

    /**
     * @param $newMessage
     */
    public function saveCaptureFailedMessage($newMessage)
    {

        if (isset($_SESSION['altapay_capture_failed'])) {
            $message = $_SESSION['altapay_capture_failed'] . "<br/>";
        } else {
            $message = "";
        }

        $_SESSION['altapay_capture_failed'] = $message . $newMessage;
    }

    /**
     * @param $newMessage
     */
    public function saveCaptureWarning($newMessage)
    {

        if (isset($_SESSION['altapay_capture_warning'])) {
            $message = $_SESSION['altapay_capture_warning'] . "<br/>";
        } else {
            $message = "";
        }

        $_SESSION['altapay_capture_warning'] = $message . $newMessage;
    }

    /**
     * displays login error message
     */
    public function loginError()
    {

        $this->showUserMessage('altapay_login_error', 'error', 'Could not login to the Merchant API: ');
    }

    /**
     * @param $field
     * @param $type
     * @param string $message
     */
    public function showUserMessage($field, $type, $message = '')
    {

        $this->startSession();

        if (!isset($_SESSION[$field])) {
            return;
        }

        echo "<div class='$type notice'> <p>$message $_SESSION[$field]</p> </div>";

        unset($_SESSION[$field]);
    }

    /**
     * displays failed capture message
     */
    public function captureFailed()
    {
        $this->showUserMessage('altapay_capture_failed', 'error');
    }

    /**
     * displays warning message against capture request
     */
    public function captureWarning()
    {

        $this->showUserMessage('altapay_capture_warning', 'update-nag');
    }

    /**
     * @param $links
     * @return array
     */
    public function addActionLinks($links)
    {
        $newLink = array(
            '<a href="' . admin_url('admin.php?page=altapay-settings') . '">Settings</a>',
        );
        return array_merge($links, $newLink);
    }


    /**
     * loads language file with language specifics
     */
    public function altapayLocalizationInit()
    {
        load_plugin_textdomain('altapay', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * add altapay settings option in plugins menu
     */
    public function altapaySettingsMenu()
    {
        add_submenu_page('woocommerce', 'Altapay Settings', 'Altapay Settings', 'manage_options',
            $this->plugin_options_key, array($this, 'altapaySettings'));
    }

    /**
     * register altapay specific settings group including url and api login credentials
     */
    public function altapayRegisterSettings()
    {
        register_setting('altapay-settings-group', 'altapay_gateway_url');
        register_setting('altapay-settings-group', 'altapay_username');
        register_setting('altapay-settings-group', 'altapay_password');
        register_setting('altapay-settings-group', 'altapay_payment_page');
        register_setting('altapay-settings-group', 'altapay_terminals_enabled', 'json_encode');
    }

    /**
     * Altapay settings page with actions and controls
     * @throws AltapayMerchantAPIException
     */
    public function altapaySettings()
    {
        $terminals = false;
        $disabledTerminals = array();
        $gatewayURL = esc_attr(get_option('altapay_gateway_url'));
        $username = get_option('altapay_username');
        $password = get_option('altapay_password');
        $paymentPage = esc_attr(get_option('altapay_payment_page'));
        $terminalDetails  = get_option('altapay_terminals');

        if (!empty($terminalDetails)) {
            $terminals = json_decode(get_option('altapay_terminals'));
        }
        $enabledTerminals = json_decode(get_option('altapay_terminals_enabled'));
        $terminalInfo = json_decode(get_option('altapay_terminals'));

        foreach ($terminalInfo as $term) {
            //The key is the terminal name
            if (!in_array($term->key, $enabledTerminals)) {
                array_push($disabledTerminals, $term->key);
            }
        }

        $pluginDir = plugin_dir_path(__FILE__);
        //Directory for the terminals
        $terminalDir = $pluginDir . '/../../terminals/';
        //Temp dir in case the one from above is not writable
        $tmpDir = sys_get_temp_dir();

        foreach ($disabledTerminals as $disabledTerm) {
            $disabledTerminalFileName = $disabledTerm . '.class.php';
            $path = $terminalDir . $disabledTerminalFileName;
            $tmpPath = $tmpDir . '/' . $disabledTerminalFileName;
            //Check if there is a terminal created so it can  be removed
            if (file_exists($path)) {
                unlink($path);
            } elseif (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        if (!$enabledTerminals || $enabledTerminals == 'null') {
            $enabledTerminals = array();
        }

        if (array_key_exists('settings-updated', $_REQUEST) && $_REQUEST['settings-updated'] == true) {
            $this->refreshTerminals();
        }
        ?>

        <div class="wrap" style="margin-top:2%;">
            <?php echo '<img style="width:10%; height:auto;" src="' . esc_url(plugins_url('../../assets/images/altapay-logo.jpg', __FILE__)) . '" > '; ?>
            <br><br>
            <div style="background: #006064; height: 30px;">
                <h2 style="color:white; line-height: 30px; padding-left: 1%;"><?php echo __('Settings', 'altapay'); ?></h2>
            </div>
            <?php
            if (version_compare(PHP_VERSION, '5.4', '<')) {
                wp_die(sprintf('Altapay for WooCommerce requires PHP 5.4 or higher. You’re still on %s.', PHP_VERSION));
            } else {
                $blade = new AltapayHelpers();
                echo $blade->loadBladeLibrary()->run("forms.adminSettings",
                    [
                        'gatewayURL' => $gatewayURL,
                        'username' => $username,
                        'password' => $password,
                        'paymentPage' => $paymentPage,
                        'terminals' => $terminals,
                        'enabledTerminals' => $enabledTerminals,
                    ]); ?>
                <script>
                    jQuery(document).ready(function ($) {
                        jQuery('#create_altapay_payment_page').unbind().on('click', function (e) {
                            var data = {
                                'action': 'create_altapay_payment_page',
                            };
                            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                            jQuery.post(ajaxurl, data, function (response) {
                                result = jQuery.parseJSON(response);
                                if (result.status == 'ok') {
                                    jQuery('#altapay_payment_page').val(result.page_id);
                                    jQuery('#payment-page-msg').text(result.message);
                                    jQuery('#create_altapay_payment_page').attr('disabled', 'disabled');
                                } else {
                                    jQuery('#payment-page-msg').text(result.message);
                                }
                            });

                        });
                    });
                </script>

                <?php
                if ($gatewayURL && $username) {
                    $this->altapayRefreshConnectionForm();
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Method for refreshing terminals on Altapay settings page
     * @throws AltapayMerchantAPIException
     */
    function refreshTerminals()
    {
        $api = $this->apiLogin();
        if ($api instanceof WP_Error) {
            $_SESSION['altapay_login_error'] = $api->get_error_message();
            echo '<p><b>' . __('Could not connect to Altapay!', 'altapay') . '</b></p>';
            //Delete terminals and enabled terminals from database
            update_option('altapay_terminals', array());
            update_option('altapay_terminals_enabled', array());
            ?>
            <script>
                setTimeout("location.reload()", 1500);
            </script>
            <?php
            return;
        }
        echo '<p><b>' . __('Connection OK !', 'altapay') . '</b></p>';
        // Get list of terminals information
        $terminalInfo = $api->getTerminals();
        $terminals = array();
        $terms = $terminalInfo->getTerminals();

        foreach ($terms as $terminal) {
            $terminals[] = array(
                'key' => str_replace([' ', '-'], '_', $terminal->getTitle()),
                'name' => $terminal->getTitle(),
                'nature' => $terminal->getNature()
            );
        }


        update_option('altapay_terminals', json_encode($terminals));
        ?>
        <script>
            setTimeout("location.reload()", 1500);
        </script>
        <?php
    }

    /**
     * Form with refresh connection button on altapay page
     */
    private function altapayRefreshConnectionForm()
    {
        $terminals = get_option('altapay_terminals');
        if (!$terminals) { ?>
            <p><?php echo __('Terminals missing, please click - Refresh connection', 'altapay') ?></p>
        <?php } else { ?>
            <p><?php echo __('Click below to re-create terminal data', 'altapay') ?></p>
        <?php } ?>
        <form method="post" action="#refresh_connection">
            <input type="hidden" value="true" name="refresh_connection">
            <input type="submit" value="<?php echo __('Refresh connection', 'altapay') ?>" name="refresh-connection"
                   class="button" style="color: #006064; border-color: #006064;">
        </form>
        <?php
        // TODO Make use of wordpress notice and error handling
        // Test connection
        if (isset($_POST['refresh_connection'])) {
            $this->refreshTerminals();
        }
    }

}