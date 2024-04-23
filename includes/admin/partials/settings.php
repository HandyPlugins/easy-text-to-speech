<?php
/**
 * Settings page
 *
 * @package EasyTTS\Admin
 */

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found

use function EasyTTS\Polly\get_supported_voices;
use function EasyTTS\Polly\render_voice_selections;
use function EasyTTS\Utils\get_license_info;
use function EasyTTS\Utils\get_license_key;
use function EasyTTS\Utils\get_license_status_message;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$settings = \EasyTTS\Utils\get_settings();
?>

<form method="post" action="" id="easytts_settings_form" name="easytts_settings_form">
	<?php wp_nonce_field( 'easytts_settings', 'easytts_settings' ); ?>
	<section>
		<div class="sui-box">

			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'Settings', 'easy-text-to-speech' ); ?></h2>
			</div>

			<div class="sui-box-body">
				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label" id="tts_provider_label"><?php esc_html_e( 'Provider', 'easy-text-to-speech' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<div class="sui-form-field sui-box-selectors">
							<ul role="radiogroup">
								<li>
									<label for="aws-polly" class="sui-box-selector">
										<input <?php checked( $settings['tts_provider'], 'aws-polly' ); ?>
											type="radio"
											name="tts_provider"
											value="aws-polly"
											id="aws-polly"
											aria-labelledby="aws-polly-label"
										>
										<span aria-hidden="true">
											<span id="aws-polly-label" aria-hidden="true"><?php esc_html_e( 'Amazon Polly', 'easy-text-to-speech' ); ?></span>
										</span>
									</label>
								</li>
								<li>
									<label for="openai" class="sui-box-selector sui-disabled">
										<input
											<?php checked( $settings['tts_provider'], 'openai' ); ?>
											type="radio"
											name="tts_provider"
											value="openai"
											id="openai"
											aria-labelledby="openai-label"
										>
										<span aria-hidden="true">
											<span id="openai-label" aria-hidden="true"><?php esc_html_e( 'OpenAI', 'easy-text-to-speech' ); ?></span>
											<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'easy-text-to-speech' ); ?></span>
										</span>
									</label>
								</li>
								<li>
									<label for="elevenlabs" class="sui-box-selector sui-disabled">
										<input
											<?php checked( $settings['tts_provider'], 'elevenlabs' ); ?>
											type="radio"
											name="tts_provider"
											value="elevenlabs"
											id="elevenlabs"
											aria-labelledby="elevenlabs-label"
										>
										<span aria-hidden="true">
											<span id="elevenlabs-label" aria-hidden="true"><?php esc_html_e( 'ElevenLabs', 'easy-text-to-speech' ); ?></span>
											<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'easy-text-to-speech' ); ?></span>
										</span>
									</label>
								</li>
							</ul>
						</div>

						<div class="sui-form-field">
							<div id="aws-polly-details" class="tts-provider-settings" style="<?php echo( 'aws-polly' !== $settings['tts_provider'] ? 'display:none' : '' ); ?>" tabindex="0">
								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="aws_polly_access_key_label"><?php esc_html_e( 'AWS Access Key', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<input
												name="aws_polly_access_key"
												id="aws_polly_access_key"
												class="sui-form-control sui-input-md"
												aria-labelledby="aws_polly_access_key_label"
												type="text"
												value="<?php echo esc_attr( \EasyTTS\Utils\mask_string( \EasyTTS\Utils\get_decrypted_value( $settings['aws_polly_access_key'] ), EasyTTS\Constants\INPUT_MASK_LENGTH ) ); ?>"
												autocomplete="off"
											/>
										</div>
									</div>
								</div>

								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="aws_polly_secret_key_label"><?php esc_html_e( 'AWS Secret Key', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<input
												name="aws_polly_secret_key"
												id="aws_polly_secret_key"
												class="sui-form-control sui-input-md"
												aria-labelledby="aws_polly_secret_key_label"
												type="text"
												value="<?php echo esc_attr( \EasyTTS\Utils\mask_string( \EasyTTS\Utils\get_decrypted_value( $settings['aws_polly_secret_key'] ), 0 ) ); ?>"
												autocomplete="off"
											/>
										</div>
									</div>
								</div>

								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="aws_polly_region"><?php esc_html_e( 'AWS Region', 'easy-text-to-speech' ); ?></span>
									</div>
									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<select name="aws_polly_region" id="aws_polly_region" class="sui-select">
												<?php foreach ( \EasyTTS\Polly\get_supported_regions() as $region => $region_name ) : ?>
													<option <?php selected( $region, $settings['aws_polly_region'] ); ?> value="<?php echo esc_attr( $region ); ?>">
														<?php echo esc_attr( $region_name ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
								</div>

								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="aws_polly_engine"><?php esc_html_e( 'Voice Engine', 'easy-text-to-speech' ); ?></span>
									</div>
									<div class="sui-box-settings-col-2">
										<div class="sui-form-field" role="radiogroup">
											<label for="aws_polly_engine_standard" class="sui-radio">
												<input
													type="radio"
													name="aws_polly_engine"
													id="aws_polly_engine_standard"
													aria-labelledby="label-aws_polly_engine_standard"
													value="standard"
													class="aws_polly_engine"
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
													class="aws_polly_engine"
													<?php checked( $settings['aws_polly_engine'], 'neural' ); ?>
												/>
												<span aria-hidden="true"></span>
												<span id="label-aws_polly_engine_neural"><?php esc_html_e( 'Neural', 'easy-text-to-speech' ); ?></span>
											</label>
										</div>
									</div>
								</div>

								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="label_easytts_default_voice"><?php esc_html_e( 'Default Voice', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<select name="aws_polly_default_voice" id="aws_polly_default_voice" class="sui-select">
												<?php if ( $settings['aws_polly_region'] && $settings['aws_polly_engine'] && $settings['aws_polly_access_key'] && $settings['aws_polly_secret_key'] ) : ?>
													<?php
													$voices = get_supported_voices(
														$settings['aws_polly_region'],
														$settings['aws_polly_engine'],
														\EasyTTS\Utils\get_decrypted_value( $settings['aws_polly_access_key'] ),
														\EasyTTS\Utils\get_decrypted_value( $settings['aws_polly_secret_key'] )
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
											<span id="aws_polly_default_voice_desc" class="sui-description"></span>

										</div>

									</div>
								</div>

								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="label_enable_ssml"><?php esc_html_e( 'SSML', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<label for="enable_ssml" class="sui-toggle">
												<input
													type="checkbox"
													id="enable_ssml"
													name="enable_ssml"
													aria-labelledby="enable_ssml_label"
													aria-describedby="enable_ssml_description"
													value="1"
													<?php checked( 1, $settings['enable_ssml'] ); ?>
												>

												<span class="sui-toggle-slider" aria-hidden="true"></span>
												<span id="enable_ssml_label" class="sui-toggle-label"><?php esc_html_e( 'Enable Speech Synthesis Markup Language (SSML).', 'easy-text-to-speech' ); ?></span>
											</label>

											<span class="sui-description">
												<?php esc_html_e( 'Leveraging SSML-enhanced text allows you to gain extra control over how the speech is generated from the input text you provide.', 'easy-text-to-speech' ); ?>
											</span>
										</div>

									</div>
								</div>

							</div>

							<div id="openai-details" class="tts-provider-settings" style=" <?php echo( 'openai' !== $settings['tts_provider'] ? 'display:none' : '' ); ?>" tabindex="0">
								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="openai_api_key_label"><?php esc_html_e( 'API Key', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<input
												name="openai_api_key"
												id="openai_api_key"
												class="sui-form-control sui-input-md"
												aria-labelledby="openai_api_key_label"
												type="text"
												value="<?php echo esc_attr( \EasyTTS\Utils\mask_string( \EasyTTS\Utils\get_decrypted_value( $settings['openai_api_key'] ), EasyTTS\Constants\INPUT_MASK_LENGTH ) ); ?>"
												autocomplete="off"
											/>
										</div>
									</div>
								</div>
								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label"><?php esc_html_e( 'TTS Model', 'easy-text-to-speech' ); ?></span>
									</div>
									<?php
									$tts_models = \EasyTTS\Constants\OPENAI_TTS_MODELS;
									?>
									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<select name="openai_tts_model" id="openai_tts_model" class="sui-select">
												<?php foreach ( $tts_models as $tts_model => $model_label ) : ?>
													<option <?php selected( $tts_model, $settings['openai_tts_model'] ); ?> value="<?php echo esc_attr( $tts_model ); ?>">
														<?php echo esc_attr( $model_label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<span class="sui-description"><?php esc_html_e( 'tts-1 is optimized for real time text to speech use cases and tts-1-hd is optimized for quality.', 'easy-text-to-speech' ); ?></span>
										</div>
									</div>
								</div>

								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label"><?php esc_html_e( 'TTS Voice', 'easy-text-to-speech' ); ?></span>
									</div>
									<?php
									$tts_voices = \EasyTTS\Constants\OPENAI_TTS_VOICES;
									?>
									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<select name="openai_tts_voice" id="openai_tts_voice" class="sui-select">
												<?php foreach ( $tts_voices as $tts_voice => $voice_label ) : ?>
													<option <?php selected( $tts_voice, $settings['openai_tts_voice'] ); ?> value="<?php echo esc_attr( $tts_voice ); ?>">
														<?php echo esc_attr( $voice_label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<span class="sui-description"><?php esc_html_e( 'The voice to use when generating the audio.', 'easy-text-to-speech' ); ?></span>
										</div>
									</div>
								</div>
							</div>

							<div id="elevenlabs-details" class="tts-provider-settings" style=" <?php echo( 'elevenlabs' !== $settings['tts_provider'] ? 'display:none' : '' ); ?>" tabindex="0">
								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="elevenlabs_api_key_label"><?php esc_html_e( 'API Key', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<input
												name="elevenlabs_api_key"
												id="elevenlabs_api_key"
												class="sui-form-control sui-input-md"
												aria-labelledby="elevenlabs_api_key_label"
												type="text"
												value="<?php echo esc_attr( \EasyTTS\Utils\mask_string( \EasyTTS\Utils\get_decrypted_value( $settings['elevenlabs_api_key'] ), EasyTTS\Constants\INPUT_MASK_LENGTH ) ); ?>"
												autocomplete="off"
											/>
										</div>
									</div>
								</div>
								<div class="sui-box-settings-row">
									<div class="sui-box-settings-col-1">
										<span class="sui-settings-label" id="elevenlabs_default_voice"><?php esc_html_e( 'Default Voice', 'easy-text-to-speech' ); ?></span>
									</div>

									<div class="sui-box-settings-col-2">
										<div class="sui-form-field">
											<select name="elevenlabs_default_voice" id="elevenlabs_default_voice" class="sui-select">
												<?php $voices = []; ?>
												<?php if ( ! empty( $voices ) ) : ?>
													<?php foreach ( $voices as $voice_id => $voice_name ) : ?>
														<option <?php selected( $voice_id, $settings['elevenlabs_default_voice'] ); ?> value="<?php echo esc_attr( $voice_id ); ?>">
															<?php echo esc_attr( $voice_name ); ?>
														</option>
													<?php endforeach; ?>
												<?php else : ?>
													<option value="-1">
														<?php esc_html_e( 'Select...', 'easy-text-to-speech' ); ?>
													</option>
												<?php endif; ?>
											</select>
											<span class="sui-description"><?php esc_html_e( 'The default voice to use when generating the audio.', 'easy-text-to-speech' ); ?></span>

										</div>

									</div>
								</div>

							</div>
						</div>
					</div>
				</div>


				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label" id="tts_disclosure_label"><?php esc_html_e( 'TTS disclosure', 'easy-text-to-speech' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<input
								name="tts_disclosure"
								id="tts_disclosure"
								class="sui-form-control"
								aria-labelledby="tts_disclosure_label"
								type="text"
								value="<?php echo esc_attr( $settings['tts_disclosure'] ); ?>"
								autocomplete="off"
								placeholder="<?php echo esc_attr__( 'The voice you are hearing is generated by AI technology, not a human.', 'easy-text-to-speech' ); ?>"
							/>
							<span class="sui-description">
								<?php esc_html_e( 'If you need to provide a clear disclosure to end users that the TTS voice they are hearing is AI-generated. You can use this field to add a disclosure for generated audios. (OpenAI requires disclosure)', 'easy-text-to-speech' ); ?>
							</span>
						</div>
					</div>
				</div>

				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label" id="role_key"><?php esc_html_e( 'Role', 'easy-text-to-speech' ); ?></span>
					</div>

					<?php
					$roles = wp_roles()->get_names();

					if ( EASYTTS_IS_NETWORK && ! isset( $roles['super_admin'] ) ) {
						$roles = [ 'super_admin' => esc_html__( 'Super Admin', 'easy-text-to-speech' ) ] + $roles;
					}

					?>
					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<select name="role" id="select-easytts-role" class="sui-select">
								<?php foreach ( $roles as $role => $role_name ) : ?>
									<option <?php selected( $role, $settings['role'] ); ?> value="<?php echo esc_attr( $role ); ?>">
										<?php echo esc_attr( translate_user_role( $role_name ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="sui-description"><?php esc_html_e( 'Minimum required role to access text to speech features', 'easy-text-to-speech' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Upsell ads -->
				<div class="sui-box-settings-row sui-upsell-row">
					<div class="sui-upsell-notice" style="padding-left: 0;">
						<p><?php esc_html_e( 'Upgrade to the Pro version to unlock exclusive access to OpenAI and Elevenlab\'s advanced text-to-speech models.', 'easy-text-to-speech' ); ?><br>
							<a href="https://handyplugins.co/easy-text-to-speech/?utm_source=wp_admin&utm_medium=plugin&utm_campaign=settings_page" rel="noopener noreferrer nofollow" target="_blank" class="sui-button sui-button-purple" style="margin-top: 10px;color:#fff;"><?php esc_html_e( 'Try Easy Text-to-Speech PRO Today', 'magic-login' ); ?></a>
						</p>
					</div>
				</div>

			</div>

			<div class="sui-box-footer">
				<div class="sui-actions-left">
					<button type="submit" name="easytts_form_action" value="save_settings" class="sui-button sui-button-blue">
						<i class="sui-icon-save" aria-hidden="true"></i>
						<?php esc_html_e( 'Update settings', 'easy-text-to-speech' ); ?>
					</button>
				</div>
			</div>

		</div>

	</section>
</form>

