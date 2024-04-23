<?php
/**
 * Core plugin functionality.
 *
 * @package EasyTTS
 */

namespace EasyTTS\Core;

use \WP_Error;
use EasyTTS\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	add_action( 'init', __NAMESPACE__ . '\\i18n' );
	add_action( 'init', __NAMESPACE__ . '\\init' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_scripts' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_styles' );
	do_action( 'easytts_loaded' );
}


/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'easy-text-to-speech' );
	load_textdomain( 'easy-text-to-speech', WP_LANG_DIR . '/easytts/easy-text-to-speech-' . $locale . '.mo' );
	load_plugin_textdomain( 'easy-text-to-speech', false, plugin_basename( EASYTTS_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
	do_action( 'easytts_init' );
}

/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array
 */
function get_enqueue_contexts() {
	return [
		'admin',
		'frontend',
		'shared',
		'editorial',
		'editor',
	];
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script  Script file name (no .js extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string|WP_Error URL
 */
function script_url( $script, $context ) {
	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in Easy Text-to-Speech script loader.' );
	}

	return EASYTTS_URL . "dist/js/{$script}.js";

}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension)
 * @param string $context    Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string|\WP_Error URL or WP Error
 * @since 1.0
 */
function style_url( $stylesheet, $context ) {
	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in Easy Text-to-Speech stylesheet loader.' );
	}

	return EASYTTS_URL . "dist/css/{$stylesheet}.css";
}

/**
 * Enqueue scripts for admin.
 *
 * @param string $hook hookname
 *
 * @return void
 */
function admin_scripts( $hook ) {
	if ( ! current_user_can( Utils\get_required_capability() ) ) {
		return;
	}

	if ( false !== stripos( $hook, 'easy-text-to-speech' ) ) {
		wp_enqueue_script(
			'easytts-admin',
			script_url( 'admin', 'admin' ),
			[
				'jquery',
				'clipboard',
				'lodash',
				'wp-i18n',
				'wp-edit-post',
				'wp-components',
				'wp-compose',
				'wp-data',
				'wp-edit-post',
				'wp-element',
				'wp-plugins',
			],
			EASYTTS_VERSION,
			true
		);

		$args = [
			'nonce' => wp_create_nonce( 'easytts_admin_nonce' ),
		];

		wp_localize_script(
			'easytts-admin',
			'EasyTTSAdmin',
			$args
		);
	}
}


/**
 * Enqueue styles for admin.
 *
 * @param string $hook Hook name
 *
 * @return void
 */
function admin_styles( $hook ) {
	$classic_editor_hooks = [ 'post-new.php', 'post.php' ];

	if ( in_array( $hook, $classic_editor_hooks, true ) || false !== stripos( $hook, 'easy-text-to-speech' ) ) {
		wp_enqueue_style(
			'easytts-admin',
			style_url( 'admin', 'admin' ),
			[],
			EASYTTS_VERSION
		);
	}
}
