<?php
/**
 * AWS Polly related functionalities
 *
 * @package EasyTTS
 */

namespace EasyTTS\Polly;

use function EasyTTS\Utils\get_decrypted_value;
use function EasyTTS\Utils\get_filesystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Supported Polly regions.
 *
 * @link https://docs.aws.amazon.com/general/latest/gr/pol.html
 * @link https://docs.aws.amazon.com/polly/latest/dg/NTTS-main.html#ntts-regions we just use the regions that both support neural voices
 * @return array
 */
function get_supported_regions() {
	return [
		'us-east-1'      => 'US East (N. Virginia)',
		'us-west-2'      => 'US West (Oregon)',
		'af-south-1'     => 'Africa (Cape Town)',
		'ap-south-1'     => 'Asia Pacific (Mumbai)',
		'ap-northeast-3' => 'Asia Pacific (Osaka)',
		'ap-northeast-2' => 'Asia Pacific (Seoul)',
		'ap-southeast-1' => 'Asia Pacific (Singapore)',
		'ap-southeast-2' => 'Asia Pacific (Sydney)',
		'ap-northeast-1' => 'Asia Pacific (Tokyo)',
		'ca-central-1'   => 'Canada (Central)',
		'eu-central-1'   => 'Europe (Frankfurt)',
		'eu-west-1'      => 'Europe (Ireland)',
		'eu-west-2'      => 'Europe (London)',
		'eu-west-3'      => 'Europe (Paris)',
		'us-gov-west-1'  => 'AWS GovCloud (US-West)',
	];
}

/**
 * Convert given text to voice and save it as attachment
 *
 * @param string $text     Text that will be converted to sound.
 * @param string $voice_id Polly voice id
 * @param string $engine   Polly engine. (standard|neural)
 * @param int    $post_id  The parent post of the attachment.
 *
 * @return int|void|\WP_Error
 */
function convert_text_to_voice( $text, $voice_id, $engine, $post_id = 0 ) {
	$settings = \EasyTTS\Utils\get_settings();

	$access_key = get_decrypted_value( $settings['aws_polly_access_key'] );
	$secret_key = get_decrypted_value( $settings['aws_polly_secret_key'] );

	if ( empty( $engine ) ) {
		$engine = $settings['aws_polly_engine'];
	}

	$text_type  = $settings['enable_ssml'] ? 'ssml' : 'text';
	$voice_text = $text;

	if ( $settings['enable_ssml'] ) {
		$voice_text = sprintf( '<speak>%s</speak>', $text );
	}

	$polly_args = [
		'Text'         => $voice_text,
		'OutputFormat' => 'mp3',
		'VoiceId'      => $voice_id,
		'Engine'       => $engine,
		'region'       => $settings['aws_polly_region'],
		'TextType'     => $text_type,
	];

	$polly_args        = apply_filters( 'easytts_polly_synthesize_speech_args', $polly_args, $text, $voice_id, $engine );
	$audio_data        = synthesize_speech( $access_key, $secret_key, $polly_args );
	$json_decoded_data = json_decode( $audio_data, true );

	if ( isset( $json_decoded_data['message'] ) ) {
		return new \WP_Error( 'audio-file-failed', $json_decoded_data['message'] );
	}

	$filename = uniqid( 'easytts_' );

	if ( $post_id > 0 ) {
		$title    = get_the_title( $post_id );
		$filename = sanitize_file_name( $title );
		$filename = wp_unique_id( sprintf( 'easytts_audio_%s', $filename ) );
	}

	$filesystem = get_filesystem();
	$filename   = apply_filters( 'easytts_file_name', $filename, $post_id );
	$tmp        = wp_tempnam( $filename );
	$audio_file = $filesystem->put_contents( $tmp, $audio_data );

	if ( ! $audio_file ) {
		return new \WP_Error( 'audio-file-failed', esc_html__( 'Unable to store the audio file in the temporary directory.', 'easy-text-to-speech' ) );
	}

	$file_array = array(
		'name'     => $filename . '.mp3',
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file_array, $post_id );
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $tmp );

		return $attachment_id;
	}

	return $attachment_id;
}

/**
 * Render option items for voice selection
 *
 * @param array  $voice_list The list of voices
 * @param string $selected   Selected Voice id
 *
 * @return false|string
 */
function render_voice_selections( $voice_list, $selected = '' ) {
	$grouped_voice_list = [];

	foreach ( $voice_list as $voice ) {
		$grouped_voice_list[ sprintf( '%s (%s)', $voice['LanguageName'], $voice['LanguageCode'] ) ][ $voice['Id'] ] = $voice;
	}

	ob_start();
	foreach ( $grouped_voice_list as $group_name => $voices ) :
		?>
		<optgroup label="<?php echo esc_attr( $group_name ); ?>">
			<?php foreach ( $voices as $voice_detail ) : ?>
				<option value="<?php echo esc_attr( $voice_detail['Id'] ); ?>" <?php selected( $selected, $voice_detail['Id'] ); ?> ><?php echo esc_html( $voice_detail['Name'] ); ?> (<?php echo esc_attr( $voice_detail['Gender'] ); ?>)</option>
			<?php endforeach; ?>
		</optgroup>
		<?php
	endforeach;

	return ob_get_clean();
}


/**
 * AWS signature
 *
 * @param array  $keys         AWS keys
 * @param string $region       AWS region
 * @param string $service      AWS service
 * @param string $host         AWS host
 * @param string $uri          AWS URI
 * @param string $request_type Request type
 * @param string $payload      Payload
 *
 * @return array
 */
function aws_signature( $keys, $region, $service, $host, $uri, $request_type = 'GET', $payload = '' ) {
	$access_key = $keys['access_key'];
	$secret_key = $keys['secret_key'];

	$method                = $request_type;
	$algorithm             = 'AWS4-HMAC-SHA256';
	$amzdate               = gmdate( 'Ymd\THis\Z' );
	$date                  = gmdate( 'Ymd' );
	$credential_scope      = $date . '/' . $region . '/' . $service . '/aws4_request';
	$canonical_uri         = $uri;
	$canonical_querystring = '';
	$canonical_headers     = "host:$host\nx-amz-date:$amzdate\n";
	$signed_headers        = 'host;x-amz-date';
	$payload_hash          = hash( 'sha256', $payload );
	$canonical_request     = "$method\n$canonical_uri\n$canonical_querystring\n$canonical_headers\n$signed_headers\n$payload_hash";

	$string_to_sign = "$algorithm\n$amzdate\n$credential_scope\n" . hash( 'sha256', $canonical_request );
	$signing_key    = get_signature_key( $secret_key, $date, $region, $service );
	$signature      = hash_hmac( 'sha256', $string_to_sign, $signing_key );

	$authorization_header = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";

	return [
		'Authorization' => $authorization_header,
		'x-amz-date'    => $amzdate,
	];
}

/**
 * Get signature key
 *
 * @param string $key          Secret key
 * @param string $date_stamp   Date stamp
 * @param string $region_name  Region name
 * @param string $service_name Service name
 *
 * @return false|string
 */
function get_signature_key( $key, $date_stamp, $region_name, $service_name ) {
	$k_secret  = 'AWS4' . $key;
	$k_date    = hash_hmac( 'sha256', $date_stamp, $k_secret, true );
	$k_region  = hash_hmac( 'sha256', $region_name, $k_date, true );
	$k_service = hash_hmac( 'sha256', $service_name, $k_region, true );
	$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

	return $k_signing;
}

/**
 * Get supported voices for the given engine
 *
 * @param string $region     AWS Region
 * @param string $engine     Engine name
 * @param string $access_key AWS Access key
 * @param string $secret_key AWS Secret key
 *
 * @return array|mixed|\WP_Error|null
 */
function get_supported_voices( $region, $engine, $access_key, $secret_key ) {
	$engine = strtolower( $engine );

	// Create a unique cache key based on the region and engine
	$cache_key = 'easytts_' . md5( 'polly_voices_' . $region . '_' . $engine );

	// Try to get the voices from the cache
	$voices = get_transient( $cache_key );

	// If the voices are not in the cache
	if ( false === $voices ) {
		// Fetch the voices
		$voices = fetch_polly_voices( $region, $access_key, $secret_key );

		// If there was an error fetching the voices, return the error
		if ( is_wp_error( $voices ) ) {
			return $voices;
		}

		if ( ! isset( $voices['Voices'] ) ) {
			return new \WP_Error( 'no-voices', esc_html__( 'No voices found.', 'easy-text-to-speech' ) );
		}

		$voices = $voices['Voices'];

		// Filter the voices based on the engine
		$voices = array_filter(
			$voices,
			function ( $voice ) use ( $engine ) {
				return in_array( $engine, $voice['SupportedEngines'], true );
			}
		);

		// Cache the voices for 5 minutes
		set_transient( $cache_key, $voices, 5 * MINUTE_IN_SECONDS );
	}

	// Return the voices
	return $voices;
}

/**
 * Fetch Polly voices
 *
 * @param string $region     AWS Region
 * @param string $access_key Access key
 * @param string $secret_key Secret key
 *
 * @return array|mixed|\WP_Error|null
 */
function fetch_polly_voices( $region, $access_key, $secret_key ) {
	$service  = 'polly';
	$host     = "polly.$region.amazonaws.com";
	$uri      = '/v1/voices';
	$endpoint = "https://$host$uri";

	$keys    = [
		'access_key' => $access_key,
		'secret_key' => $secret_key,
	];
	$headers = aws_signature( $keys, $region, $service, $host, $uri, 'GET' );

	$response = wp_remote_get(
		$endpoint,
		[
			'headers'     => [
				'Authorization' => $headers['Authorization'],
				'x-amz-date'    => $headers['x-amz-date'],
			],
			'httpversion' => '1.1',
		]
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = wp_remote_retrieve_body( $response );

	return json_decode( $body, true );
}


/**
 * Synthesize speech
 *
 * @param string $access_key AWS Access key
 * @param string $secret_key AWS Secret key
 * @param array  $polly_args Arguments for Polly
 *
 * @return array|string|\WP_Error
 */
function synthesize_speech( $access_key, $secret_key, $polly_args ) {
	$service  = 'polly';
	$host     = "polly.{$polly_args['region']}.amazonaws.com";
	$uri      = '/v1/speech';
	$endpoint = "https://$host$uri";

	$payload = wp_json_encode( $polly_args );

	$keys    = [
		'access_key' => $access_key,
		'secret_key' => $secret_key,
	];
	$headers = aws_signature( $keys, $polly_args['region'], $service, $host, $uri, 'POST', $payload );

	$response = wp_remote_post(
		$endpoint,
		[
			'headers'     => [
				'Authorization' => $headers['Authorization'],
				'x-amz-date'    => $headers['x-amz-date'],
				'Content-Type'  => 'application/json',
			],
			'body'        => $payload,
			'httpversion' => '1.1',
		]
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = wp_remote_retrieve_body( $response );

	return $body;
}
