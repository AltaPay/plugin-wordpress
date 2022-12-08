# AltaPay for WooCommerce

Integrates your WooCommerce web shop to the AltaPay payments gateway.

If you are not a developer, please use [AltaPay for WooCommerce](https://wordpress.org/plugins/altapay-for-woocommerce/) on WordPress.org.

## How to Build

If you wish to build your own copy, follow below steps:

- Navigate to the `plugins` directory and run below commands.

        git clone https://github.com/AltaPay/plugin-wordpress.git
        cd plugin-wordpress
        
- Install all the necessary dependencies.
        
        composer install --no-dev
- Finally, Activate the plugin from the plugins page.

## How to run cypress tests
As a prerequisite install WooCommerce with default theme (Storefront) & sample data and follow below steps:

* Navigate to `tests/integration-test`
* Install cypress by executing 

        npm i
        
* Update `cypress/fixtures/config.json`
* Run cypress

        ./node_modules/.bin/cypress open
        
## Code Analysis
PHPStan is being used for running static code analysis. Its configuration file 'phpstan.neno.dist' is available in this repository. The directories are mentioned under the scnDirectories option, in phpstan.neon.dist file, are required for running the analysis. These directories belong to WordPress and WooCommerce. If you don't have these packages, you'll need to download and extract them first and then make sure their paths are correctly reflected in phpstan.neon.dist file. Once done, we can run the analysis: 
* Install composer packages using `composer install`
* Run `vendor/bin/phpstan analyze` to run the analysis. It'll print out any errors detected by PHPStan.

## Changelog

See [Changelog](CHANGELOG.md) for all the release notes.

## License

Distributed under the GNU General Public License. See [LICENSE](LICENSE) for more information.

## Documentation

For more details please see [docs](https://github.com/AltaPay/plugin-wordpress/wiki)
