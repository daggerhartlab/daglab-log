<?php
/**
 * Plugin uninstall handler
 *
 * This file is called when the plugin is completely uninstalled (deleted).
 * It removes all plugin data from the database.
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

// Only delete data when plugin is completely uninstalled
\DagLabLog\LogTableManager::dropTable();