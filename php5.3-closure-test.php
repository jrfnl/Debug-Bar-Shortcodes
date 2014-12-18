<?php
/**
 * Debug Bar Shortcodes - is_closure test
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

if( ! function_exists( 'debug_bar_shortcodes_is_closure' ) ) {
	/**
	 * Check if a callback is a closure
	 *
	 * @param   mixed	$arg	Function name
	 * @return  boolean
	 */
	function debug_bar_shortcodes_is_closure( $arg ) {
		$test = function() {
		};
		$is_closure = ( $arg instanceof $test );
		return $is_closure;
	}
}