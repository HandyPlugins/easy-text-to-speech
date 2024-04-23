<?php
/**
 * Dashboard Page
 *
 * @package EasyTTS
 */

namespace EasyTTS\Admin\Dashboard;

use EasyTTS\Encryption;
use function EasyTTS\Polly\convert_text_to_voice;
use function EasyTTS\Polly\get_supported_voices;
use function EasyTTS\Polly\render_voice_selections;
use function EasyTTS\Utils\get_decrypted_value;
use function EasyTTS\Utils\get_required_capability;
use function EasyTTS\Utils\mask_string;
use const EasyTTS\Constants\INPUT_MASK_LENGTH;
use const EasyTTS\Constants\MENU_SLUG;
use const EasyTTS\Constants\SETTING_OPTION;

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Setup routine
 *
 * @return void
 */
function setup() {
	if ( EASYTTS_IS_NETWORK ) {
		add_action( 'network_admin_menu', __NAMESPACE__ . '\\network_admin_menu' );
	} else {
		add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );
	}

	add_filter( 'admin_body_class', __NAMESPACE__ . '\\add_sui_admin_body_class' );
	add_action( 'wp_ajax_easytts_voice_list', __NAMESPACE__ . '\\voice_list_callback' );
	add_action( 'wp_ajax_easytts_generate_voice', __NAMESPACE__ . '\\generate_voice_callback' );
	add_action( 'admin_init', __NAMESPACE__ . '\\save_settings' );
}

/**
 * Add required class for shared UI
 *
 * @param string $classes css classes for admin area
 *
 * @return string
 * @see    https://wpmudev.github.io/shared-ui/installation/
 * @since  1.0
 */
function add_sui_admin_body_class( $classes ) {
	$classes .= ' sui-2-12-24 ';

	return $classes;
}


/**
 * Add network admin menu
 *
 * @return void
 */
function network_admin_menu() {
	add_submenu_page(
		'settings.php',
		esc_html__( 'Easy Text-to-Speech', 'easy-text-to-speech' ),
		esc_html__( 'Easy Text-to-Speech', 'easy-text-to-speech' ),
		'manage_network',
		MENU_SLUG,
		__NAMESPACE__ . '\settings_screen'
	);
}

/**
 * Add admin menu
 *
 * @return void
 */
function admin_menu() {
	add_options_page(
		esc_html__( 'Easy Text-to-Speech', 'easy-text-to-speech' ),
		esc_html__( 'Easy Text-to-Speech', 'easy-text-to-speech' ),
		'manage_options',
		MENU_SLUG,
		__NAMESPACE__ . '\settings_screen'
	);
}


/**
 * Settings page
 *
 * @since 1.0
 */
function settings_screen() { ?>
	<main class="sui-wrap">
		<?php include EASYTTS_INC . 'admin/partials/header.php'; ?>
		<?php include EASYTTS_INC . 'admin/partials/settings.php'; ?>
		<?php include EASYTTS_INC . 'admin/partials/footer.php'; ?>
	</main>
	<?php
}


/**
 * Save settings
 *
 * @return array $settings Settings
 * @since 1.0
 */
function save_settings() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$nonce = filter_input( INPUT_POST, 'easytts_settings', FILTER_SANITIZE_SPECIAL_CHARS );
	if ( wp_verify_nonce( $nonce, 'easytts_settings' ) ) {
		$old_settings                         = \EasyTTS\Utils\get_settings();
		$settings                             = [];
		$settings['enable_ssml']              = ! empty( $_POST['enable_ssml'] );
		$settings['role']                     = sanitize_text_field( filter_input( INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['aws_polly_region']         = sanitize_text_field( filter_input( INPUT_POST, 'aws_polly_region', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['aws_polly_engine']         = sanitize_text_field( filter_input( INPUT_POST, 'aws_polly_engine', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['aws_polly_default_voice']  = sanitize_text_field( filter_input( INPUT_POST, 'aws_polly_default_voice', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['tts_disclosure']           = sanitize_text_field( filter_input( INPUT_POST, 'tts_disclosure', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['openai_tts_model']         = sanitize_text_field( filter_input( INPUT_POST, 'openai_tts_model', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['openai_tts_voice']         = sanitize_text_field( filter_input( INPUT_POST, 'openai_tts_voice', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['tts_provider']             = sanitize_text_field( filter_input( INPUT_POST, 'tts_provider', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$settings['elevenlabs_default_voice'] = sanitize_text_field( filter_input( INPUT_POST, 'elevenlabs_default_voice', FILTER_SANITIZE_SPECIAL_CHARS ) );

		$aws_polly_access_key = sanitize_text_field( filter_input( INPUT_POST, 'aws_polly_access_key' ) );
		$aws_polly_secret_key = sanitize_text_field( filter_input( INPUT_POST, 'aws_polly_secret_key' ) );

		// prev keys for comparison
		$masked_polly_access_key_prev = mask_string( get_decrypted_value( $old_settings['aws_polly_access_key'] ), INPUT_MASK_LENGTH );
		$masked_polly_secret_key_prev = mask_string( get_decrypted_value( $old_settings['aws_polly_secret_key'] ), 0 );

		if ( mask_string( $aws_polly_access_key, INPUT_MASK_LENGTH ) === $masked_polly_access_key_prev ) {
			$aws_polly_access_key = get_decrypted_value( $old_settings['aws_polly_access_key'] );
		}

		if ( $aws_polly_secret_key === $masked_polly_secret_key_prev ) {
			$aws_polly_secret_key = get_decrypted_value( $old_settings['aws_polly_secret_key'] );
		}

		$encryption = new Encryption();

		$settings['aws_polly_access_key'] = $encryption->encrypt( $aws_polly_access_key );
		$settings['aws_polly_secret_key'] = $encryption->encrypt( $aws_polly_secret_key );

		if ( EASYTTS_IS_NETWORK ) {
			update_site_option( SETTING_OPTION, $settings );
		} else {
			update_option( SETTING_OPTION, $settings, false );
		}

		add_settings_error( SETTING_OPTION, 'easy-text-to-speech', esc_html__( 'Settings saved.', 'easy-text-to-speech' ), 'success' );

		return $settings;
	}

}

/**
 * Ajax callback for supported voice list for AWS Polly
 *
 * @return void
 * @since 1.0
 */
function voice_list_callback() {
	if ( ! check_ajax_referer( 'easytts_admin_nonce', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => esc_html__( 'You can not perform this action!', 'easy-text-to-speech' ) ] );
	}
	$settings = \EasyTTS\Utils\get_settings();

	if ( isset( $_POST['data'] ) ) {
		parse_str( wp_unslash( $_POST['data'] ), $form_data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$region     = sanitize_text_field( $form_data['aws_polly_region'] );
		$engine     = sanitize_text_field( $form_data['aws_polly_engine'] );
		$access_key = sanitize_text_field( $form_data['aws_polly_access_key'] );
		$secret_key = sanitize_text_field( $form_data['aws_polly_secret_key'] );

		if ( mask_string( $form_data['aws_polly_access_key'], INPUT_MASK_LENGTH ) === $access_key ) {
			$access_key = get_decrypted_value( $settings['aws_polly_access_key'] );
		}

		if ( mask_string( $form_data['aws_polly_secret_key'], INPUT_MASK_LENGTH ) === $secret_key ) {
			$secret_key = get_decrypted_value( $settings['aws_polly_secret_key'] );
		}
	} else {
		$region     = $settings['aws_polly_region'];
		$access_key = get_decrypted_value( $settings['aws_polly_access_key'] );
		$secret_key = get_decrypted_value( $settings['aws_polly_secret_key'] );
		$engine     = ! empty( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : $settings['aws_polly_engine']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	}

	if ( empty( $access_key ) || empty( $secret_key ) ) {
		wp_send_json_error( [ 'message' => esc_html__( 'Empty access key or secret!', 'easy-text-to-speech' ) ] );
	}

	$voices = get_supported_voices( $region, $engine, $access_key, $secret_key );

	if ( is_wp_error( $voices ) ) {
		wp_send_json_error( [ 'message' => $voices->get_error_message() ] );
	}

	$rendered_list = render_voice_selections( $voices, $settings['aws_polly_default_voice'] );

	wp_send_json_success( [ 'html' => $rendered_list ] );
}

/**
 * Voice generation ajax callback
 *
 * @return void
 */
function generate_voice_callback() {
	$settings = \EasyTTS\Utils\get_settings();
	if ( ! check_ajax_referer( 'easytts_admin_nonce', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => esc_html__( 'You can not perform this action!', 'easy-text-to-speech' ) ] );
	}

	if ( ! current_user_can( get_required_capability() ) ) {
		wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to perform this action!', 'easy-text-to-speech' ) ] );
	}

	parse_str( wp_unslash( $_POST['data'] ), $form_data ); // phpcs:ignore

	if ( empty( trim( $form_data['content'] ) ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Content is required to generate voice.', 'easy-text-to-speech' ),
			)
		);
	}

	$post_id = 0;

	if ( isset( $form_data['post_id'] ) && 0 < absint( $form_data['post_id'] ) ) {
		$post_id = absint( $form_data['post_id'] );
	}

	if ( 'aws-polly' === $settings['tts_provider'] ) {
		if ( empty( $form_data['aws_polly_voice'] ) || '-1' === $form_data['aws_polly_voice'] ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You need to choose a voice to speak for you.', 'easy-text-to-speech' ),
				)
			);
		}

		$attachment_id = convert_text_to_voice( $form_data['content'], $form_data['aws_polly_voice'], $form_data['aws_polly_engine'], $post_id );
	}

	if ( is_wp_error( $attachment_id ) ) {
		wp_send_json_error(
			array(
				'message' => $attachment_id->get_error_message(),
			)
		);
	}

	wp_send_json_success(
		[
			'attachment_id'  => absint( $attachment_id ),
			'attachment_url' => wp_get_attachment_url( $attachment_id ),
		]
	);
}
