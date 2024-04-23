<?php
/**
 * Plugin Name:       Easy Text-to-Speech
 * Plugin URI:        https://handyplugins.co/easy-text-to-speech/
 * Description:       Turn text into high-quality speech with Amazon Polly, OpenAI, and ElevenLabs.
 * Version:           1.0
 * Requires at least: 5.7
 * Requires PHP:      7.2.5
 * Author:            HandyPlugins
 * Author URI:        https://handyplugins.co/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       easy-text-to-speech
 * Domain Path:       /languages
 *
 * @package EasyTTS
 */

namespace EasyTTS;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Useful global constants.
define( 'EASYTTS_VERSION', '1.0' );
define( 'EASYTTS_DB_VERSION', '2.0' );
define( 'EASYTTS_PLUGIN_FILE', __FILE__ );
define( 'EASYTTS_URL', plugin_dir_url( __FILE__ ) );
define( 'EASYTTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'EASYTTS_INC', EASYTTS_PATH . 'includes/' );

// deactivate pro
if ( defined( 'EASYTTS_PRO_PLUGIN_FILE' ) ) {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	deactivate_plugins( plugin_basename( EASYTTS_PRO_PLUGIN_FILE ) );

	return;
}


// Require Composer autoloader if it exists.
if ( file_exists( EASYTTS_PATH . 'vendor/autoload.php' ) ) {
	include_once EASYTTS_PATH . 'vendor/autoload.php';
}

/**
 * PSR-4-ish autoloading
 *
 * @since 2.0
 */
spl_autoload_register(
	function ( $class ) {
		// project-specific namespace prefix.
		$prefix = 'EasyTTS\\';

		// base directory for the namespace prefix.
		$base_dir = __DIR__ . '/includes/classes/';

		// does the class use the namespace prefix?
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// if the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Include files.
require_once EASYTTS_INC . 'constants.php';
require_once EASYTTS_INC . 'utils.php';
require_once EASYTTS_INC . 'polly.php';
require_once EASYTTS_INC . 'core.php';
require_once EASYTTS_INC . 'editor.php';
require_once EASYTTS_INC . 'admin/dashboard.php';


$network_activated = Utils\is_network_wide( EASYTTS_PLUGIN_FILE );
if ( ! defined( 'EASYTTS_IS_NETWORK' ) ) {
	define( 'EASYTTS_IS_NETWORK', $network_activated );
}


/**
 * Bootstrap the plugin.
 *
 * @return void
 */
function bootstrap() {
	Core\setup();
	Editor\setup();
	Admin\Dashboard\setup();
	Install::setup();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );
