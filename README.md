# Altapay for WooCommerce

Integrates your WooCommerce web shop to the AltaPay payments gateway.

## Changelog

3.2.2
> * Support provided for Woocommerce version 5.0.0

3.2.1
> * Fix some notification errors

3.2.0
> * Fixed the overlapping notification bar issue
> * Code improvement

3.1.1
> * Added fix for payment page CSS

3.1.0
> * Rebranding from Valitor to Altapay
> * Added payment methods logo selection functionality
> * Support provided for Wordpress version 5.5
> * Support provided for Woocommerce version 4.3.2

3.0.1
> * Fix - saved credit card deletion

3.0.0
> * Added plugin disclaimer
> * Added support for WooCommerce version 3.9.2 and Wordpress version 5.3.2
> * Added support for auto-fill credit card details when using credit card token
> * Major refactoring for improving the source code quality
> * Added support for Klarna Payments (Klarna reintegration)
> * Added release payment functionality, by:
> 	- using release payment button from the actions panel
> 	- changing order status to canceled state
> * Added design improvements: settings page and action panel
> * Refactored payment form template to render appropriate order information

2.5.0
> * Added support for:
>   - multiple tax rates with compound configurations
>   - multiple coupon discounts for variable products
> * Source code refactoring according to PSR-2

2.4.0
> * Added support for bundle products
> * Improved the partial captures on orderlines

2.3.0
> * Added support for various coupon types and variation products
> * Improvements when dealing with tax included/excluded amounts
> * Fix - failed partial captures and refunds when Klarna used

2.2.0
> * Compatibility with the latest WooCommerce version 3.7.0
> * Added unit tests
> * Improved error handling
> * Fix: - tax calculation and price rules getting wrong amounts in certain situations

2.1.1
> * Fix - unit price not fetched correctly when price including taxes

2.1.0
> * Added support for coupons
> * Cart rules are parsed as a separate order line to the payment gateway
> * Fix - unit price without taxes, regardless the setting from the backend

2.0.0
> * Strengthen solution for the virtual products in relation to the shipping information
> * Fix - error when fetching the plugin information
> * Fix - error log spammed with error messages due to the wrong autoloader implementation

1.9.0
> * SDK rebranding from Altapay to Valitor
> * Added support for WooCommerce 3.6.3 and WordPress 5.2.0

1.8.0
> * Platform and plugin versioning information sent to the payment gateway

1.7.2
> * Fix - Error message shown if create payment call fails
> * Fix - Payment gateway password with special characters parsed correctly

1.7.1
> * Fix - Small cosmetic fixes after rebranding

1.7.0
> * Rebranding from Altapay to Valitor
> * Update the Wordpress and WooCommerce supported versions
> * Fix - extension update

1.6.3
> * Fix - Rename the PHP SDK package and update the references

1.6.2
> * Improvements - Refund operation updates the stock with the refunded products, if order lines are sent

1.6.1
> * Add new tags for WooCommerce required version and tested up to
> * Fix - compatibility with WooCommerce up to 3.3.3
> * Improvements - PHP SDK

1.6.0
> * PHP SDK update.

1.5.1
> * Fix - Capture and Release buttons.
> * Perform tests with latest WordPress version.

1.5.0
> * Include Valitor PHP SDK through Composer.
> * Upgrade the build package script.

1.4.0
> * Show cart info in the payment page.

1.3.4
> * Fix - connection to the payment gateway.

1.3.3
> * Fix - Valitor terminals are not visible if connection to the API is not established.

1.3.2
> * Fix - JavaScript code.

1.3.1
> * Improve the refund section.
> * Fix - captured amount shown in the view.
> * Fix - no value in the quantity input field from the order lines.

1.3.0
> * Add order lines for partial capture/refund.
> * Add the sales_tax value, calculated for partial capture.
> * Add refund functionality in the same code block as capture.
> * Add shipping details as part of the order lines; hence, the shipping can be refunded.

1.2.14
> * Fix - sales_tax parameter not sent to the payment gateway.

1.2.13
> * Fix - regarding languages.

1.2.12
> * Fix - regarding refunds.

1.2.11
> * Correction for compatibility with WooCommerce 3.0.
>     - Upgrade Notice - [Review update best practices](https://docs.woocommerce.com/document/how-to-update-your-site) before upgrading.

1.2.10
> * Orders are captured when their statuses are changed to Completed.

1.2.9
> * Correction in templates loading.

1.2.8
> * Add order lines to partial refunds.

1.2.7
> * Several fixes.

1.2.6
> * Security improvements.

1.2.5
> * Add support for alternative payment methods.

1.2.1
> * First stable version.

## How to run cypress tests

### Prerequisites: 

* WordPress default theme and WooCommerce should be installed and running on a public URL
* Cypress should be installed

### Steps 

* Install dependencies `npm i`
* Update "cypress/fixtures/config.json"
* Execute `./node_modules/.bin/cypress run` in the terminal to run all the tests

## Code Analysis
PHPStan is being used for running static code analysis. Its configuration file 'phpstan.neno.dist' is available in this repository. The directories are mentioned under the scnDirectories option, in phpstan.neon.dist file, are required for running the analysis. These directories belong to WordPress and WooCommerce. If you don't have these packages, you'll need to download and extract them first and then make sure their paths are correctly reflected in phpstan.neon.dist file. Once done, we can run the analysis: 
* Install composer packages using `composer install`
* Run `vendor/bin/phpstan analyze` to run the analysis. It'll print out any errors detected by PHPStan.

## Loading and saving gateway configurations
Follow these steps to load and save the terminal configurations from the gateway.
* Move the file from `terminal-config/altapay_config.php` to the root directory of the WordPress installation
* Edit the file and replace `~gatewayusername~`,`~gatewaypass~`, and `~gatewayurl~` with the actual credentials.
* Run the file with the below command

    $ php altapay_config.php
