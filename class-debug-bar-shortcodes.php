<?php
/**
 * Debug Bar Shortcodes - Debug Bar Panel
 *
 * @package		WordPress\Plugins\Debug Bar Shortcodes
 * @author		Juliette Reinders Folmer <wpplugins_nospam@adviesenzo.nl>
 * @link		https://github.com/jrfnl/Debug-Bar-Shortcodes
 * @since		1.0
 * @version		1.0
 *
 * @copyright	2013 Juliette Reinders Folmer
 * @license		http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2 or higher
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
	// Extending Debug Bar
	class Debug_Bar_Shortcodes extends Debug_Bar_Panel {

		const DBS_STYLES_VERSION = '1.0';
		
		const DBS_SCRIPT_VERSION = '1.0';

		const DBS_NAME = 'debug-bar-shortcodes';

		public function init() {
			load_plugin_textdomain( self::DBS_NAME, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			$this->title( __( 'Shortcodes', self::DBS_NAME ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		public function enqueue_scripts() {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
			wp_enqueue_style( self::DBS_NAME, plugins_url( 'css/' . self::DBS_NAME . $suffix . '.css', __FILE__ ), array( 'debug-bar' ), self::DBS_STYLES_VERSION );
			wp_enqueue_script( self::DBS_NAME, plugins_url( 'js/' . self::DBS_NAME . $suffix . '.js', __FILE__ ), array( 'jquery' ), self::DBS_SCRIPT_VERSION, true );
//jquery-effects-highlight 
			wp_localize_script( self::DBS_NAME, 'i18n_db_shortcodes', $this->dbs_get_javascript_i18n() );
		}


		/**
		 * Retrieve the strings for use in the javascript file
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
				'php_error'			=> __( 'No proper response received. You probably have WP_DEBUG enabled and a php error occurred.', self::DBS_NAME ),
				'illegal'			=> __( 'Illegal request received.', self::DBS_NAME ),
				'failed'			=> __( 'Ajax request failed. Please try again.', self::DBS_NAME ),
				'nonce'				=> wp_create_nonce( self::DBS_NAME ),
				'spinner'			=> admin_url( 'images/wpspin_light.gif' ),
			);
			return array_merge( $strings );
		}



		public function prerender() {
			$this->set_visible( count( $GLOBALS['shortcode_tags'] ) > 0 );
		}
		
		public function render() {
			include_once( plugin_dir_path( __FILE__ ) . 'class-debug-bar-shortcodes-renderer.php' );
			$start = microtime();
			$render = new Debug_Bar_Shortcodes_Renderer();
			$render->render();
			echo 'Bench: ' . $this->microtime_diff( $start ) . ' seconds';
		}
		
	    /**    Calculate a precise time difference.
	        @param string $start result of microtime()
	        @param string $end result of microtime(); if NULL/FALSE/0/'' then it's now
	        @return flat difference in seconds, calculated with minimum precision loss
	    */
	    function microtime_diff( $start, $end=NULL ) {
	        if( !$end ) {
	            $end= microtime();
	        }
	        list($start_usec, $start_sec) = explode(" ", $start);
	        list($end_usec, $end_sec) = explode(" ", $end);
	        $diff_sec= intval($end_sec) - intval($start_sec);
	        $diff_usec= floatval($end_usec) - floatval($start_usec);
	        return floatval( $diff_sec ) + $diff_usec;
	    }

	} // End of class Debug_Bar_Shortcodes
} // End of if class_exists wrapper