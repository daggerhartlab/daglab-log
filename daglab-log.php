<?php

/**
 * Plugin Name: Daggerhart Lab - Error Logging
 * Plugin URI: https://github.com/daggerhartlab/daglab-log
 * Description: Logging plugin for WordPress.
 * Version: 1.0.0
 * Author: Daggerhart Lab
 * Author URI: https://daggerhartlab.com
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: daglab-logging
 */
define('DAGLAB_LOG_PLUGIN_FILE', __FILE__);
require_once __DIR__ . '/vendor/autoload.php';

// Register activation/deactivation hooks
register_activation_hook(__FILE__, '\DagLabLog\LogTableManager::createTable');
register_deactivation_hook(__FILE__, '\DagLabLog\LogTableManager::dropTable');

// Check for table upgrades.
add_action('plugins_loaded', '\DagLabLog\LogTableManager::maybeUpdate');

\DagLabLog\Logging\ErrorHandler::bootstrap();
\DagLabLog\Admin\SettingsPage::bootstrap();
\DagLabLog\Admin\LogsPage::bootstrap();
\DagLabLog\Admin\LogMessagePage::bootstrap();
\DagLabLog\Cron\LogCleanup::bootstrap();
\DagLabLog\Cron\EmailDigest::bootstrap();

if (true) {
	// Test different types of errors
//	trigger_error("This is a test warning", E_USER_WARNING);

//	trigger_error("This is a test notice", E_USER_NOTICE);
//
//	try {
//		throw new Exception("This is a test exception");
//	} catch (Exception $e) {
//		// Let it be handled by the exception handler
//		throw $e;
//	}

	// Channel logger.
//	$logger = new \DagLabLog\Logging\ChannelLogger('testing123');
//	$logger->error('This is a test error.');

	// Email digest.
//	add_action('init', function () {
//		(new \DagLabLog\Cron\EmailDigest())->process();
//	});
}
