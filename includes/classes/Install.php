<?php
/**
 * Installation functionalities
 *
 * @package EasyTTS
 */

namespace EasyTTS;

use const EasyTTS\Constants\DB_VERSION_OPTION;


/**
 * Class Install
 */
class Install {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'check_version' ], 5 );
	}

	/**
	 * Return an instance of the current class
	 *
	 * @since 1.0
	 */
	public static function setup() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Check DB version and run the updater is required.
	 */
	public function check_version() {
		if ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
			return;
		}

		if ( version_compare( get_option( DB_VERSION_OPTION ), EASYTTS_DB_VERSION, '<' ) ) {
			$this->install();
			/**
			 * Fires after plugin update.
			 *
			 * @hook  easytts_updated
			 * @since 1.0
			 */
			do_action( 'easytts_updated' );
		}
	}

	/**
	 * Perform Installation
	 */
	public function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		$lock_key = 'easytts_installing';
		// Check if we are not already running
		if ( $this->has_lock( $lock_key ) ) {
			return;
		}

		// lets set the transient now.
		$this->set_lock( $lock_key );

		if ( EASYTTS_IS_NETWORK ) {
			$this->maybe_upgrade_network_wide();
		} else {
			$this->maybe_upgrade();
		}

		$this->remove_lock( $lock_key );
	}


	/**
	 * Check if a lock exists of the upgrade routine
	 *
	 * @param string $lock_name transient name
	 *
	 * @return bool
	 */
	private function has_lock( $lock_name ) {
		if ( EASYTTS_IS_NETWORK ) {
			if ( 'yes' === get_site_transient( $lock_name ) ) {
				return true;
			}

			return false;
		}

		if ( 'yes' === get_transient( $lock_name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set the lock
	 *
	 * @param string $lock_name transient name for the lock
	 *
	 * @return bool
	 */
	private function set_lock( $lock_name ) {
		if ( EASYTTS_IS_NETWORK ) {
			return set_site_transient( $lock_name, 'yes', MINUTE_IN_SECONDS );
		}

		return set_transient( $lock_name, 'yes', MINUTE_IN_SECONDS );
	}

	/**
	 * Remove lock
	 *
	 * @param string $lock_name transient name for the lock
	 *
	 * @return bool
	 */
	private function remove_lock( $lock_name ) {
		if ( EASYTTS_IS_NETWORK ) {
			return delete_site_transient( $lock_name );
		}

		return delete_transient( $lock_name );
	}


	/**
	 * Upgrade routine for network wide activation
	 */
	public function maybe_upgrade_network_wide() {
		if ( version_compare( get_site_option( DB_VERSION_OPTION ), EASYTTS_DB_VERSION, '<' ) ) {
			update_site_option( DB_VERSION_OPTION, EASYTTS_DB_VERSION );
		}
	}

	/**
	 * Upgrade routine
	 */
	public function maybe_upgrade() {
		if ( version_compare( get_option( DB_VERSION_OPTION ), EASYTTS_DB_VERSION, '<' ) ) {
			update_option( DB_VERSION_OPTION, EASYTTS_DB_VERSION );
		}
	}


}

