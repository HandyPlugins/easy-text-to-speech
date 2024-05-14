<?php
/**
 * Settings Page Footer
 *
 * @package EasyTTS\Admin
 */

use const EasyTTS\Constants\BLOG_URL;
use const EasyTTS\Constants\DOCS_URL;
use const EasyTTS\Constants\FAQ_URL;
use const EasyTTS\Constants\GITHUB_URL;
use const EasyTTS\Constants\SUPPORT_URL;
use const EasyTTS\Constants\TWITTER_URL;

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- ELEMENT: The Brand -->
<div class="sui-footer">
	<?php
	echo wp_kses_post(
		sprintf(
		/* translators: %s: https://handyplugins.co/ */
			__( 'Made with <i class="sui-icon-heart"></i> by <a href="%s" rel="noopener" target="_blank">HandyPlugins</a>', 'easy-text-to-speech' ),
			'https://handyplugins.co/'
		)
	);
	?>
</div>

<footer>
	<!-- ELEMENT: Navigation -->
	<ul class="sui-footer-nav">
		<li><a href="<?php echo esc_url( FAQ_URL ); ?>" target="_blank"><?php esc_html_e( 'FAQ', 'easy-text-to-speech' ); ?></a></li>
		<li><a href="<?php echo esc_url( BLOG_URL ); ?>" target="_blank"><?php esc_html_e( 'Blog', 'easy-text-to-speech' ); ?></a></li>
		<li><a href="<?php echo esc_url( SUPPORT_URL ); ?>" target="_blank"><?php esc_html_e( 'Support', 'easy-text-to-speech' ); ?></a></li>
		<li><a href="<?php echo esc_url( DOCS_URL ); ?>" target="_blank"><?php esc_html_e( 'Docs', 'easy-text-to-speech' ); ?></a></li>
	</ul>

	<!-- ELEMENT: Social Media -->
	<ul class="sui-footer-social">
		<li><a href="<?php echo esc_url( GITHUB_URL ); ?>" target="_blank">
				<i class="sui-icon-social-github" aria-hidden="true"></i>
				<span class="sui-screen-reader-text">GitHub</span>
			</a></li>
		<li><a href="<?php echo esc_url( TWITTER_URL ); ?>" target="_blank">
				<i class="sui-icon-social-twitter" aria-hidden="true"></i></a>
			<span class="sui-screen-reader-text">Twitter</span>
		</li>
	</ul>
</footer>
