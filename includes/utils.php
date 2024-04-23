<?php
/**
 * Utility functions for the plugin.
 *
 * @link    https://developer.wordpress.org/themes/basics/template-tags/
 * @package EasyTTS
 */

namespace EasyTTS\Utils;

use EasyTTS\Encryption;
use const EasyTTS\Constants\SETTING_OPTION;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get asset info from extracted asset files
 *
 * @param string $slug      Asset slug as defined in build/webpack configuration
 * @param string $attribute Optional attribute to get. Can be version or dependencies
 *
 * @return string|array
 */
function get_asset_info( $slug, $attribute = null ) {
	if ( file_exists( EASYTTS_PATH . 'dist/js/' . $slug . '.asset.php' ) ) {
		$asset = include EASYTTS_PATH . 'dist/js/' . $slug . '.asset.php';
	} elseif ( file_exists( EASYTTS_PATH . 'dist/css/' . $slug . '.asset.php' ) ) {
		$asset = include EASYTTS_PATH . 'dist/css/' . $slug . '.asset.php';
	} else {
		return null;
	}

	if ( ! empty( $attribute ) && isset( $asset[ $attribute ] ) ) {
		return $asset[ $attribute ];
	}

	return $asset;
}


/**
 * Is plugin activated network wide?
 *
 * @param string $plugin_file file path
 *
 * @return bool
 * @since  1.0
 */
function is_network_wide( $plugin_file ) {
	if ( ! is_multisite() ) {
		return false;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		include_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	return is_plugin_active_for_network( plugin_basename( $plugin_file ) );
}

/**
 * Get settings with defaults
 *
 * @return array
 * @since  1.0
 */
function get_settings() {
	$defaults = [
		'tts_provider'             => 'aws-polly',
		'tts_disclosure'           => '',
		'role'                     => 'administrator',
		'aws_polly_access_key'     => '',
		'aws_polly_secret_key'     => '',
		'aws_polly_default_voice'  => '',
		'aws_polly_region'         => 'eu-central-1',
		'aws_polly_engine'         => '',
		'openai_tts_voice'         => 'alloy',
		'openai_tts_model'         => 'tts-1',
		'openai_api_key'           => '',
		'elevenlabs_api_key'       => '',
		'elevenlabs_default_voice' => '',
		'enable_ssml'              => true,
	];

	if ( EASYTTS_IS_NETWORK ) {
		$settings = get_site_option( SETTING_OPTION, [] );
	} else {
		$settings = get_option( SETTING_OPTION, [] );
	}

	$settings = wp_parse_args( $settings, $defaults );

	return $settings;
}

/**
 * Get minimum required capability to use easytts
 *
 * @return mixed|null
 * @since 1.0
 */
function get_required_capability() {
	$settings = \EasyTTS\Utils\get_settings();

	if ( 'super_admin' === $settings['role'] ) {
		$capability = 'manage_network';
	} else {
		$capabilities = get_role( $settings['role'] )->capabilities;
		$capabilities = array_keys( $capabilities );
		$capability   = $capabilities[0];
	}

	return apply_filters( 'easytts_required_capability', $capability );
}


/**
 * ports \settings_errors for SUI
 *
 * @param string $setting        Slug title of a specific setting
 * @param bool   $sanitize       Whether to re-sanitize the setting value before returning errors
 * @param bool   $hide_on_update Whether hide or not hide on update
 *
 * @see settings_errors
 */
function settings_errors( $setting = '', $sanitize = false, $hide_on_update = false ) {

	if ( $hide_on_update && ! empty( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$settings_errors = get_settings_errors( $setting, $sanitize );

	if ( empty( $settings_errors ) ) {
		return;
	}

	$output = '';

	foreach ( $settings_errors as $key => $details ) {
		if ( 'updated' === $details['type'] ) {
			$details['type'] = 'sui-notice-success';
		}

		if ( in_array( $details['type'], array( 'error', 'success', 'warning', 'info' ), true ) ) {
			$details['type'] = 'sui-notice-' . $details['type'];
		}

		$css_id = sprintf(
			'setting-error-%s',
			esc_attr( $details['code'] )
		);

		$css_class = sprintf(
			'sui-notice %s settings-error is-dismissible',
			esc_attr( $details['type'] )
		);

		$output .= "<div id='$css_id' class='$css_class'> \n";
		$output .= "<div class='sui-notice-content'><div class='sui-notice-message'>";
		$output .= "<span class='sui-notice-icon sui-icon-info sui-md' aria-hidden='true'></span>";
		$output .= "<p>{$details['message']}</p></div></div>";
		$output .= "</div> \n";
	}

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Get license endpoint
 *
 * @return string
 * @since 1.0
 */
function get_license_endpoint() {
	return LICENSE_ENDPOINT;
}


/**
 * Mask given string
 *
 * @param string $input_string  String
 * @param int    $unmask_length The lenght of unmask
 *
 * @return string
 * @since 1.0
 */
function mask_string( $input_string, $unmask_length ) {
	$output_string = substr( $input_string, 0, $unmask_length );

	if ( strlen( $input_string ) > $unmask_length ) {
		$output_string .= str_repeat( '*', strlen( $input_string ) - $unmask_length );
	}

	return $output_string;
}

/**
 * Get decrypted value
 *
 * @param string $value encrypted value
 *
 * @return bool|mixed|string
 */
function get_decrypted_value( $value ) {
	$encryption      = new Encryption();
	$decrypted_value = $encryption->decrypt( $value );

	if ( false !== $decrypted_value ) {
		return $decrypted_value;
	}

	return $value;
}


/**
 * Get filesystem
 *
 * @return \WP_Filesystem_Base
 */
function get_filesystem() {
	global $wp_filesystem;

	if ( ! $wp_filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	return $wp_filesystem;
}
