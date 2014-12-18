<?php
/**
 * Debug Bar Shortcodes - Debug Bar Panel
 *
 * @package     WordPress\Plugins\Debug Bar Shortcodes
 * @author      Juliette Reinders Folmer <wpplugins_nospam@adviesenzo.nl>
 * @link        https://github.com/jrfnl/Debug-Bar-Shortcodes
 * @since       1.0
 * @version     1.0.3
 *
 * @copyright   2013-2015 Juliette Reinders Folmer
 * @license     http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2 or higher
 */

// Avoid direct calls to this file
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


/**
 * The classes in this file extend the functionality provided by the parent plugin "Debug Bar".
 */
if ( ! class_exists( 'Debug_Bar_Shortcodes' ) && class_exists( 'Debug_Bar_Panel' ) ) {
	/**
	 * Debug Bar Shortcodes - Debug Bar Panel
	 */
	class Debug_Bar_Shortcodes extends Debug_Bar_Panel {

		const DBS_STYLES_VERSION = '1.0';
		
		const DBS_SCRIPT_VERSION = '1.0.1';

		const DBS_NAME = 'debug-bar-shortcodes';


		/**
		 * Set up our panel
		 */
		public function init() {
			load_plugin_textdomain( self::DBS_NAME, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			$this->title( __( 'Shortcodes', self::DBS_NAME ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'dbs_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'dbs_enqueue_scripts' ) );
		}


		/**
		 * Enqueue our scripts and styles
		 */
		public function dbs_enqueue_scripts() {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );

			wp_enqueue_style(
				self::DBS_NAME,
				plugins_url( 'css/' . self::DBS_NAME . $suffix . '.css', __FILE__ ),
				array( 'debug-bar' ),
				self::DBS_STYLES_VERSION
			);
			wp_enqueue_script(
				self::DBS_NAME,
				plugins_url( 'js/' . self::DBS_NAME . $suffix . '.js', __FILE__ ),
				array( 'jquery', 'wp-ajax-response' ),
				self::DBS_SCRIPT_VERSION,
				true
			);
			// reminder for js @todo jquery-effects-highlight
			wp_localize_script(
				self::DBS_NAME,
				'i18n_db_shortcodes',
				$this->dbs_get_javascript_i18n()
			);
		}


		/**
		 * Retrieve the strings for use in our javascript
		 *
		 * @return  array
		 */
		public function dbs_get_javascript_i18n() {
			
			$strings = array(
				'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
				'hide_details'		=> __( 'Hide details', self::DBS_NAME ),
				'view_details'		=> __( 'View details', self::DBS_NAME ),
				'no_details'		=> __( 'No details found', self::DBS_NAME ),
				'hide_use'			=> __( 'Hide Uses', self::DBS_NAME ),
				'view_use'			=> __( 'View Uses', self::DBS_NAME ),
				'not_in_use'		=> __( 'Not Used', self::DBS_NAME ),
				'error'				=> __( 'Ajax request failed or no proper response received. If you have WP_DEBUG enabled, this might be caused by a php error. The js error console might contain more information.', self::DBS_NAME ),
				'illegal'			=> __( 'Illegal request received.', self::DBS_NAME ),
				'nonce'				=> wp_create_nonce( self::DBS_NAME ),
				'spinner'			=> admin_url( 'images/wpspin_light.gif' ),
			);
			return array_merge( $strings );
		}


		/**
		 * Show only if there are registered shortcodes
		 * Unless someone de-registers the wp standard shortcodes, should always evaluate to true
		 */
		public function prerender() {
			$this->set_visible( is_array( $GLOBALS['shortcode_tags'] ) && $GLOBALS['shortcode_tags'] !== array() );
		}


		/**
		 * Render the panel
		 */
		public function render() {
			include_once( plugin_dir_path( __FILE__ ) . 'class-debug-bar-shortcodes-info.php' );
			$info = new Debug_Bar_Shortcodes_Info();
			$info->display();
		}

	} // End of class Debug_Bar_Shortcodes
} // End of if class_exists wrapper