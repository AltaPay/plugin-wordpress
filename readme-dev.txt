== Code Analysis ==

PHPStan is being used for running static code analysis. Its configuration file 'phpstan.neno.dist' is available in this repository. The directories are mentioned under the scnDirectories option, in phpstan.neon.dist file, are required for running the analysis. These directories belong to WordPress and WooCommerce. If you don't have these packages, you'll need to download and extract them first and then make sure their paths are correctly reflected in phpstan.neon.dist file. Once done, we can run the analysis: 
1. First install composer packages using 'composer install'
2. Then run 'vendor/bin/phpstan analyze' to run the analysis. It'll print out any errors detected by PHPStan.