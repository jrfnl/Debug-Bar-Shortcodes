<?php

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