<?php
/**
 * Settings Page Header
 *
 * @package EasyTTS\Admin
 */

use const EasyTTS\Constants\DOCS_URL;
use const EasyTTS\Constants\ICON_BASE64;

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<header class="sui-header">
	<img height="30" width="40" alt="<?php esc_attr_e( 'EasyTTS Icon', 'easy-text-to-speech' ); ?>"
		 src="<?php echo ICON_BASE64; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<h1 class="sui-header-title">
		<?php esc_html_e( 'Easy Text-to-Speech', 'easy-text-to-speech' ); ?>
	</h1>

	<!-- Float element to Right -->
	<div class="sui-actions-right">
		<a href="<?php echo esc_url( DOCS_URL ); ?>" class="sui-button sui-button-blue" target="_blank">
			<i class="sui-icon-academy" aria-hidden="true"></i>
			<?php esc_html_e( 'Documentation', 'easy-text-to-speech' ); ?>
		</a>
	</div>
</header>
