<?php
/**
 * Editor integration
 *
 * @package EasyTTS
 */

namespace EasyTTS\Editor;

use function EasyTTS\Polly\get_supported_voices;
use function EasyTTS\Polly\render_voice_selections;
use function EasyTTS\Core\script_url;
use function EasyTTS\Utils\get_decrypted_value;
use function EasyTTS\Utils\get_required_capability;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Setup routine
 *
 * @return void
 */
function setup() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\editor_assets' );
	add_action( 'media_buttons', __NAMESPACE__ . '\\add_media_buttons' );
	add_action( 'admin_footer', __NAMESPACE__ . '\\render_template' );
	add_filter( 'media_send_to_editor', __NAMESPACE__ . '\\add_audio_disclosure', 10, 2 );
}

/**
 * Add media button to classic editor
 *
 * @param string $editor_id Current editor ID eg: content
 *
 * @return void
 */
function add_media_buttons( $editor_id ) {
	?>
	<button type="button" class="button easytts-classic-editor-btn" data-editor-id="<?php echo esc_attr( $editor_id ); ?>">
		<span class="dashicons dashicons-controls-volumeon wp-media-buttons-icon"></span>
		<?php esc_html_e( 'Text to Speech', 'easy-text-to-speech' ); ?>
	</button>
	<?php
}

/**
 * Render template for voice generation UI/modal
 *
 * @return true|void
 */
function render_template() {
	global $hook_suffix;
	$allowed_pages = [ 'post-new.php', 'post.php' ];

	if ( ! in_array( $hook_suffix, $allowed_pages, true ) ) {
		return true;
	}

	$settings = \EasyTTS\Utils\get_settings();
	?>

	<main id="easytts-classic-editor-meta-wrapper" class="sui-wrap">
		<div class="sui-modal sui-modal-xl">
			<div role="dialog" id="easytts-modal" class="sui-modal-content" aria-live="polite" aria-modal="true" aria-labelledby="easytts-modal-title" aria-describedby="easytts-modal-desc">
				<form id="easytts-voice-generator-form">
					<?php wp_nonce_field( 'easytts_admin_nonce', 'content_nonce' ); ?>

					<input type="hidden" name="post_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
					<input type="hidden" name="tts_disclosure" id="easytts_tts_disclosure" value="<?php echo esc_attr( $settings['tts_disclosure'] ); ?>">
					<input type="hidden" id="easytts-editor-id" name="editor_id" value="">
					<div class="sui-box">
						<div class="sui-box-header">

							<h3 id="easytts-modal-title" class="sui-box-title"><?php esc_html_e( 'Text to Speech', 'easy-text-to-speech' ); ?></h3>

							<button class="sui-button-icon sui-button-float--right" id="easytts-modal-close">
								<span class="sui-icon-close sui-md" aria-hidden="true"></span>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this modal', 'easy-text-to-speech' ); ?></span>
							</button>

						</div>

						<div id="easytts-modal-message" class="sui-box-body">
							<div class="sui-form-field">
								<span class="sui-settings-label" id="label-easytts-content"><?php esc_html_e( 'What text do you want to convert to voice?', 'easy-text-to-speech' ); ?></span>

								<textarea
									name="content"
									id="easytts-content"
									class="sui-form-control"
									aria-labelledby="label-easytts-content"
									style="    width: 100%;
											max-width: 100%;
											min-height: 300px;"
									required
								></textarea>
							</div>

							<?php if ( 'aws-polly' === $settings['tts_provider'] ) : ?>
								<div id="aws-polly-details">
									<div class="sui-form-field" role="radiogroup">
										<span class="sui-settings-label" id="aws_polly_engine"><?php esc_html_e( 'Voice Engine', 'easy-text-to-speech' ); ?></span>
										<label for="aws_polly_engine_standard" class="sui-radio">
											<input
												type="radio"
												name="aws_polly_engine"
												id="aws_polly_engine_standard"
												aria-labelledby="label-aws_polly_engine_standard"
												value="standard"
												class="aws_polly_engine_for_content"
												<?php checked( $settings['aws_polly_engine'], 'standard' ); ?>
											/>
											<span aria-hidden="true"></span>
											<span id="label-aws_polly_engine_standard"><?php esc_html_e( 'Standard', 'easy-text-to-speech' ); ?></span>
										</label>
										<label for="aws_polly_engine_neural" class="sui-radio">
											<input
												type="radio"
												name="aws_polly_engine"
												id="aws_polly_engine_neural"
												aria-labelledby="label-aws_polly_engine_neural"
												value="neural"
												class="aws_polly_engine_for_content"
												<?php checked( $settings['aws_polly_engine'], 'neural' ); ?>
											/>
											<span aria-hidden="true"></span>
											<span id="label-aws_polly_engine_neural"><?php esc_html_e( 'Neural', 'easy-text-to-speech' ); ?></span>
										</label>
									</div>

									<div class="sui-form-field">
										<span class="sui-settings-label" id="label_easytts_voice"><?php esc_html_e( 'Voice', 'easy-text-to-speech' ); ?></span>
										<select name="aws_polly_voice" id="aws_polly_voice" class="sui-select">
											<?php if ( $settings['aws_polly_region'] && $settings['aws_polly_engine'] && $settings['aws_polly_access_key'] && $settings['aws_polly_secret_key'] ) : ?>
												<?php
												$voices = get_supported_voices(
													$settings['aws_polly_region'],
													$settings['aws_polly_engine'],
													get_decrypted_value( $settings['aws_polly_access_key'] ),
													get_decrypted_value( $settings['aws_polly_secret_key'] )
												);

												if ( ! is_wp_error( $voices ) ) {
													$rendered_list = render_voice_selections( $voices, $settings['aws_polly_default_voice'] );
													echo $rendered_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												}
												?>
											<?php else : ?>
												<option value="-1">
													<?php esc_html_e( 'Select...', 'easy-text-to-speech' ); ?>
												</option>
											<?php endif; ?>
										</select>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( 'openai' === $settings['tts_provider'] ) : ?>
								<div id="openai-details">
									<div class="sui-box-settings-row">
										<?php
										$tts_models = \EasyTTS\Constants\OPENAI_TTS_MODELS;
										?>
										<div class="sui-form-field">
											<span class="sui-settings-label"><?php esc_html_e( 'TTS Model', 'handywriter' ); ?></span>
											<select name="openai_tts_model" id="openai_tts_model" class="sui-select">
												<?php foreach ( $tts_models as $tts_model => $model_label ) : ?>
													<option <?php selected( $tts_model, $settings['openai_tts_model'] ); ?> value="<?php echo esc_attr( $tts_model ); ?>">
														<?php echo esc_attr( $model_label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<span class="sui-description"><?php esc_html_e( 'tts-1 is optimized for real time text to speech use cases and tts-1-hd is optimized for quality.', 'handywriter' ); ?></span>
										</div>
									</div>

									<div class="sui-box-settings-row">
										<?php
										$tts_voices = \EasyTTS\Constants\OPENAI_TTS_VOICES;
										?>
										<div class="sui-form-field">
											<span class="sui-settings-label"><?php esc_html_e( 'TTS Voice', 'handywriter' ); ?></span>

											<select name="openai_tts_voice" id="openai_tts_voice" class="sui-select">
												<?php foreach ( $tts_voices as $tts_voice => $voice_label ) : ?>
													<option <?php selected( $tts_voice, $settings['openai_tts_voice'] ); ?> value="<?php echo esc_attr( $tts_voice ); ?>">
														<?php echo esc_attr( $voice_label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<span class="sui-description"><?php esc_html_e( 'The voice to use when generating the audio.', 'handywriter' ); ?></span>
										</div>
									</div>
								</div>
							<?php endif; ?>


							<?php if ( 'elevenlabs' === $settings['tts_provider'] ) : ?>
								<div id="openai-details">
									<div class="sui-box-settings-row">
										<?php
										$elevenlabs_voices = [];
										?>
										<div class="sui-form-field">
											<span class="sui-settings-label"><?php esc_html_e( 'TTS Voice', 'handywriter' ); ?></span>

											<select name="elevenlabs_tts_voice" id="elevenlabs_tts_voice" class="sui-select">
												<?php foreach ( $elevenlabs_voices as $tts_voice => $voice_label ) : ?>
													<option <?php selected( $tts_voice, $settings['elevenlabs_default_voice'] ); ?> value="<?php echo esc_attr( $tts_voice ); ?>">
														<?php echo esc_attr( $voice_label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<span class="sui-description"><?php esc_html_e( 'The voice to use when generating the audio.', 'handywriter' ); ?></span>
										</div>
									</div>
								</div>
							<?php endif; ?>


						</div>

						<div class="sui-box-footer">
							<span id="generate_voice_result_msg" class="sui-description"></span>
							<div class="sui-actions-right">
								<button class="sui-button sui-button-blue" type="submit" id="easytts-generate-voice" aria-live="polite">
									<!-- Default State Content -->
									<span class="sui-button-text-default"><?php esc_html_e( 'Generate', 'easy-text-to-speech' ); ?></span>

									<!-- Loading State Content -->
									<span class="sui-button-text-onload">
										<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
										<?php esc_html_e( 'Generating...', 'easy-text-to-speech' ); ?>
									</span>

								</button>
							</div>
						</div>

					</div>
				</form>
			</div>
		</div>

	</main>
	<?php
}


/**
 * Register editor assets
 *
 * @return void
 * @since 1.0
 */
function editor_assets() {
	if ( ! current_user_can( get_required_capability() ) ) {
		return;
	}

	wp_register_script(
		'easytts-editor',
		script_url( 'editor', 'editor' ),
		[
			'jquery',
			'lodash',
			'wp-i18n',
			'wp-edit-post',
			'wp-components',
			'wp-compose',
			'wp-data',
			'wp-edit-post',
			'wp-element',
		],
		EASYTTS_VERSION,
		true
	);

	wp_enqueue_script( 'easytts-editor' );

	wp_set_script_translations(
		'easytts-editor',
		'easy-text-to-speech',
		plugin_dir_path( EASYTTS_PLUGIN_FILE ) . 'languages'
	);

	$current_screen  = get_current_screen();
	$is_block_editor = method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor();

	$args = [
		'isBlockEditor' => $is_block_editor,
	];

	wp_localize_script(
		'easytts-editor',
		'EasyTTSEditor',
		$args
	);
}

/**
 * Add audio disclosure on classic editor
 *
 * @param string $html Attachment HTML.
 * @param int    $id   Attachment ID.
 *
 * @return string
 */
function add_audio_disclosure( $html, $id ) {
	$attachment = get_post( $id );

	if ( 'audio/mpeg' === $attachment->post_mime_type ) {
		$title    = get_the_title( $id );
		$filename = sanitize_file_name( $title );
		$filename = sprintf( 'easytts_%s', $filename );
		$filename = apply_filters( 'easytts_file_name', $filename, $id );

		if ( false !== strpos( $filename, 'easytts_' ) ) {
			$settings = \EasyTTS\Utils\get_settings();
			if ( ! empty( $settings['tts_disclosure'] ) ) {
				$html .= '<p class="easytts-audio-disclosure">' . esc_html( $settings['tts_disclosure'] ) . '</p>';
			}
		}
	}

	return $html;
}
