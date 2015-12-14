<?php
/**
 * Debug Bar Shortcodes - Debug Bar Shortcode Info LHR.
 *
 * @package     WordPress\Plugins\Debug Bar Shortcodes
 * @subpackage  WordPress\Plugins\Debug Bar Shortcodes\Shortcode Info
 * @author      Juliette Reinders Folmer <wpplugins_nospam@adviesenzo.nl>
 * @link        https://github.com/jrfnl/Debug-Bar-Shortcodes
 * @since       2.0
 * @version     2.0
 *
 * @copyright   2013-2015 Juliette Reinders Folmer
 * @license     http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2 or higher
 */

if ( ! class_exists( 'Debug_Bar_Shortcode_Info_LHR' ) ) :

	/**
	 * Information on a shortcode provided in the lhr shortcode plugin format.
	 *
	 * @see [LHR-Shortcode list](http://wordpress.org/plugins/lrh-shortcode-list/)
	 */
	class Debug_Bar_Shortcode_Info_LHR extends Debug_Bar_Shortcode_Info_Defaults {

		/**
		 * LHR Defaults
		 *
		 * @var array $lhr_defaults
		 */
		private $lhr_defaults = array();

		/**
		 * Info array as retrieved by running the LHR `sim_{$shortcode}` filter.
		 *
		 * This info is in the LHR format and has not been validated.
		 *
		 * @var array $lhr_info
		 */
		private $lhr_info;


		/**
		 * Constructor - set the properties based on the lhr shortcode filter.
		 *
		 * @param string $shortcode The shortcode for which to retrieve info.
		 */
		public function __construct( $shortcode = '' ) {
			$this->lhr_defaults = array(
				'scTag'		=> $shortcode,
				'scName'	=> $shortcode,
				'scDesc'	=> __( 'No information available', 'debug-bar-shortcodes' ),
				'scSelfCls'	=> 'u', // Unknown.
				'scReqP'	=> array(),
				'scOptP'	=> array(),
			);
			$this->lhr_info     = apply_filters( 'sim_' . $shortcode, $this->lhr_defaults );

			$this->set_name();
			$this->set_description();
			$this->set_self_closing();
			$this->set_parameters_required();
			$this->set_parameters_optional();
		}


		/**
		 * Set the name property if the LHR filter provided us with usable information.
		 */
		private function set_name() {
			if ( is_string( $this->lhr_info['scName'] ) && $this->lhr_info['scName'] !== $this->lhr_defaults['scName'] ) {
				$this->name = $this->lhr_info['scName'];
			}
		}


		/**
		 * Set the description property if the LHR filter provided us with usable information.
		 */
		private function set_description() {
			if ( is_string( $this->lhr_info['scDesc'] ) && $this->lhr_info['scDesc'] !== $this->lhr_defaults['scDesc'] ) {
				$this->description = $this->lhr_info['scDesc'];
			}
		}


		/**
		 * Set the self_closing property if the LHR filter provided us with usable information.
		 */
		private function set_self_closing() {
			if ( is_bool( $this->lhr_info['scSelfCls'] ) ) {
				$this->self_closing = $this->lhr_info['scSelfCls'];
			}
		}


		/**
		 * Set the required key of the parameter property if the LHR filter provided us with usable information.
		 */
		private function set_parameters_required() {
			if ( is_array( $this->lhr_info['scReqP'] ) && $this->lhr_info['scReqP'] !== array() ) {
				$this->parameters['required'] = $this->lhr_info['scReqP'];
			}
		}


		/**
		 * Set the optional key of the parameter property if the LHR filter provided us with usable information.
		 */
		private function set_parameters_optional() {
			if ( is_array( $this->lhr_info['scOptP'] ) && $this->lhr_info['scOptP'] !== array() ) {
				$this->parameters['optional'] = $this->lhr_info['scOptP'];
			}
		}
	} // End of class.

endif; // End of if class_exists.
