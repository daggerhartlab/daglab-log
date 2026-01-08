<?php

/**
 * Plugin Name: Daggerhart Lab - Error Logging
 * Plugin URI: https://github.com/daggerhartlab/daglab-log
 * GitHub Plugin URI: daggerhartlab/daglab-log
 * Primary Branch: main
 * Description: Logging plugin for WordPress.
 * Version: 1.0.4
 * Author: Daggerhart Lab
 * Author URI: https://daggerhartlab.com
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: daglab-logging
 */
define('DAGLAB_LOG_PLUGIN_FILE', __FILE__);
require_once __DIR__ . '/vendor/autoload.php';

// Register activation hook
register_activation_hook(__FILE__, '\DagLabLog\LogTableManager::createTable');

// Check for table upgrades.
add_action('plugins_loaded', '\DagLabLog\LogTableManager::maybeUpdate');

\DagLabLog\Logging\ErrorHandler::bootstrap();
\DagLabLog\Admin\SettingsPage::bootstrap();
\DagLabLog\Admin\LogsPage::bootstrap();
\DagLabLog\Admin\LogMessagePage::bootstrap();
\DagLabLog\Cron\LogCleanup::bootstrap();
\DagLabLog\Cron\EmailDigest::bootstrap();
