<?php
/**
 * Uninstall EasyTTS
 * Deletes all plugin related data and configurations
 *
 * @package EasyTTS
 */

use const EasyTTS\Constants\SETTING_OPTION;
use const EasyTTS\Constants\DB_VERSION_OPTION;

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// don't perform uninstall if pro version is active
if ( defined( 'EASYTTS_PRO_PLUGIN_FILE' ) ) {
	return;
}

require_once 'plugin.php';

if ( EASYTTS_IS_NETWORK ) {
	delete_site_option( SETTING_OPTION );
	delete_site_option( DB_VERSION_OPTION );
} else {
	delete_option( SETTING_OPTION );
	delete_option( DB_VERSION_OPTION );
}
