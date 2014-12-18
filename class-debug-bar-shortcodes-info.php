<?php
/**
 * Debug Bar Shortcodes - Shortcodes Info
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
if ( ! class_exists( 'Debug_Bar_Shortcodes_Info' ) && class_exists( 'Debug_Bar_Shortcodes' ) ) {
	/**
	 * Debug Bar Shortcodes - Debug Bar Panel Renderer
	 */
	class Debug_Bar_Shortcodes_Info {
		
		/**
		 * @var	string	$name	Plugin name for use in localization, class names etc
		 */
		public static $name = 'debug-bar-shortcodes';
		
		/**
		 * @var	array	$info_defaults
		 *				[name]			=>	string	Friendly name for the shortcode
		 *				[description]	=>	string	Longer description of what the shortcode is for
		 *				[self_closing]	=>	bool	Whether the shortcode is self-closing
		 *												true if of form: [code],
		 *												false if of form: [code][/code]
		 *												Defaults to null (=unknown)
		 *				[parameters]	=>	array	Parameters which can be passed to the shortcode
		 *						[required]		=>	array	Required parameters, array format:
		 *													key		= name of the parameter
		 *													value	= description of the parameter
		 *						[optional]		=>	array	Optional parameters, array format
		 *													is the same as for required parameters.
		 */
		public $info_defaults = array(
			'name'			=> '',
			'description'	=> '',
			'self_closing'	=> null,
			'parameters'	=> array(
				'required'		=> array(),
				'optional'		=> array(),
			),
			'info_url'		=> '',
		);

		/**
		 * @var	array	The names of the shortcodes which are included with WP by default
		 */
		public $wp_shortcodes = array(
			'audio',
			'video',
			'caption',
			'wp_caption',
			'gallery',
			'embed',
			'playlist',
		);
		
		/**
		 * @var	int		The amount of shortcodes before the table header will be doubled at the bottom of the table
		 */
		public $double_min = 8;



		
		/**
		 * Register our shortcode info filters
		 */
		public function __construct() {
			add_filter( 'db_shortcodes_info', array( $this, 'parse_lhr_shortcode_info' ), 8, 2 ); // Low priority to allow override by better data
			
			
			foreach ( $this->wp_shortcodes as $shortcode ) {
				if ( method_exists( __CLASS__, 'enrich_sc_' . $shortcode ) ) {
					add_filter( 'db_shortcodes_info_' . $shortcode, array( $this, 'enrich_sc_' . $shortcode ) );
				}
			}
			unset( $shortcode );

			// Better to run via ajax if and when needed as slow
			//add_filter( 'db_shortcodes_info', array( $this, 'reflection_retrieve_shortcode_info' ), 12, 2 ); // Last option, will only run if info is bare
		}


		/**
		 * Render the actual panel
		 */
		public function display() {
			$shortcodes = $GLOBALS['shortcode_tags'];
			
			$count  = count( $shortcodes );
			$double = ( $count >= $this->double_min ? true : false ); // whether to repeat the row labels at the bottom of the table

			echo '
		<h2><span>' . esc_html__( 'Total Registered Shortcodes:', self::$name ) . '</span>' . $count . '</h2>';


			$output = '';

			if ( is_array( $shortcodes ) && $shortcodes !== array() ) {
				
				uksort( $shortcodes, 'strnatcasecmp' );
				
				$is_singular = ( is_main_query() && is_singular() );
				$header_row  = $this->render_table_header( $is_singular );

				$output .= '
				<table id="' . esc_attr( self::$name ) . '">
					<thead>' . $header_row . '</thead>
					'. ( $double === true ? '<tfoot>' . $header_row . '</tfoot>' : '' ) . '
					<tbody>';


				$i = 1;
				foreach ( $shortcodes as $shortcode => $callback ) {

					$info = $this->retrieve_shortcode_info( $shortcode );
					$has_details = ( $info !== $this->info_defaults );
					$class = ( $i % 2 ? '' : ' class="even"' );

					$output .= '
						<tr' . $class . '>
							<td>' . $i . '</td>
							<td class="column-title">
								[<strong><code>' . esc_html( $shortcode ) . '</code></strong>]
								' . $this->render_action_links( $shortcode, $has_details, $info ) . '
							</td>
							<td>' . $this->determine_callback_type( $callback ) . '</td>';

					if ( $is_singular === true ) {
						$in_use  = $this->has_shortcode( $shortcode );
						$output .= '
							<td>' . $this->render_image_based_on_bool( array( 'true' => __( 'Shortcode is used', self::$name ), 'false' => __( 'Shortcode not used', self::$name ) ), $in_use, true ) . '</td>
							<td>' . ( $in_use === true ? $this->find_shortcode_usage( $shortcode ) : '&nbsp;' ) . '</td>';
						unset( $in_use );
					}

					$output .= '
						</tr>';
						
					if ( $has_details === true ) {
						$class   = ( $i % 2 ? ' class="' . esc_attr( self::$name . '-details' ) . '"' : ' class="even ' . esc_attr( self::$name . '-details' ) . '"' );
						$output .= '
						<tr' . $class . '>
							<td>&nbsp;</td>
							<td colspan="' . ( $is_singular === true ? 4 : 2 ) . '">
								' . $this->render_details_table( $shortcode, $info ) . '
							</td>
						</tr>
						';
					}
					$i++;
				}
				unset( $shortcode, $callback, $info, $has_details, $class, $i );

				$output .= '
					</tbody>
				</table>';

			}
			else {
				$output = '<p>' . __( 'No shortcodes found', self::$name ) . '</p>';
			}
			
			echo $output;
		}


		/**
		 * Generate the table header/footer row html
		 *
		 * @param	bool	$is_singular	Whether we are viewing a singular page/post/post type
		 * @return	string
		 */
		public function render_table_header( $is_singular ) {
			$output = '<tr>
							<th>#</th>
							<th>' . esc_html__( 'Shortcode', self::$name ) . '</th>
							<th>' . esc_html__( 'Rendered by', self::$name ) . '</th>';

			if ( $is_singular === true ) {
				$output .= '
							<th>' . esc_html__( 'In use?', self::$name ) . '</th>
							<th>' . esc_html__( 'Usage', self::$name ) . '</th>';
			}

			$output .= '</tr>';

			return $output;
		}
		
		
		/**
		 * Generate the action links for a shortcode
		 *
		 * @param	string	$shortcode		Current shortcode
		 * @param	bool	$has_details	Whether or not the $info is equal to the defaults
		 * @param	array	$info			Shortcode info
		 * @return	string
		 */
		public function render_action_links( $shortcode, $has_details, $info ) {
			$links = array();

			if ( $has_details === true ) {
				$links[] = '<a href="#" class="' . esc_attr( self::$name . '-view-details' ) . '" title="' . __( 'View more detailed information about the shortcode.', self::$name ) . '">' . esc_html__( 'View details', self::$name ) . '</a>';
			}
			else {
				$links[] = '<a href="#' . esc_attr( $shortcode ) . '" class="' . esc_attr( self::$name . '-get-details' ) . '" title="' . __( 'Try and retrieve more detailed information about the shortcode.', self::$name ) . '">' . esc_html__( 'Retrieve details', self::$name ) . '</a>';
			}

			$links[] = '<a href="#' . esc_attr( $shortcode ) . '" class="' . esc_attr( self::$name . '-find' ) . '" title="' . __( 'Find out where this shortcode is used (if at all)', self::$name ) . '">' . esc_html__( 'Find uses', self::$name ) . '</a>';

			if ( $has_details === true && $info['info_url'] !== '' ) {
				$links[] = $this->render_view_online_link( $info['info_url'] );
			}
			
			return '<span class="spinner"></span><div class="row-actions">' . implode( ' | ', $links ) . '</div>';
		}
		
		/**
		 * Generate 'View online' link
		 * @internal separated from render_action_links() to also be able to use it as supplemental for ajax retrieve
		 *
		 * @param	string	$url
		 * @return	string
		 */
		public function render_view_online_link( $url ) {
			return '<a href="' . esc_url( $url ) . '" target="_blank" title="' . __( 'View extended info about the shortcode on the web', self::$name ) . '" class="' . esc_attr( self::$name . '-external-link' ) . '" >' . esc_html__( 'View online', self::$name ) . '</a>';
		}


		/**
		 * Function to retrieve a displayable string representing the callback
		 *
		 * @internal similar to callback determination in the Debug Bar Actions and Filters plugin,
		 * keep them in line with each other.
		 *
		 * @param	mixed	$callback
		 * @return	string
		 */
		public function determine_callback_type( $callback ) {

			if ( ( ! is_string( $callback ) && ! is_object( $callback ) ) && ( ! is_array( $callback ) || ( is_array( $callback ) && ( ! is_string( $callback[0] ) && ! is_object( $callback[0] ) ) ) ) ) {
				// Type 1 - not a callback
				return '';
			}
			else if ( $this->is_closure( $callback ) ) {
				// Type 2 - closure
				return '[<em>closure</em>]';
			}
			else if ( ( is_array( $callback ) || is_object( $callback ) ) && $this->is_closure( $callback[0] ) ) {
				// Type 3 - closure within an array/object
				return '[<em>closure</em>]';
			}
			else if ( is_string( $callback ) && strpos( $callback, '::' ) === false ) {
				// Type 4 - simple string function (includes lambda's)
				return sanitize_text_field( $callback ) . '()';
			}
			else if ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
				// Type 5 - static class method calls - string
				return '[<em>class</em>] ' . str_replace( '::', ' :: ', sanitize_text_field( $callback ) ) . '()';
			}
			else if ( is_array( $callback ) && ( is_string( $callback[0] ) && is_string( $callback[1] ) ) ) {
				// Type 6 - static class method calls - array
				return '[<em>class</em>] ' . sanitize_text_field( $callback[0] ) . ' :: ' . sanitize_text_field( $callback[1] ) . '()';
			}
			else if ( is_array( $callback ) && ( is_object( $callback[0] ) && is_string( $callback[1] ) ) ) {
				// Type 7 - object method calls
				return '[<em>object</em>] ' . get_class( $callback[0] ) . ' -> ' . sanitize_text_field( $callback[1] ) . '()';
			}
			else {
				// Type 8 - undetermined
				return '<pre>' . var_export( $callback, true ) . '</pre>';
			}
		}


		/**
		 * Whether the current (singular) post contains the specified shortcode
		 *
		 * Freely based on WP native implementation:
		 * Source	http://core.trac.wordpress.org/browser/trunk/src/wp-includes/shortcodes.php#L144
		 *
		 * @global	object	$post		Current post object
		 * @static	array	$matches	Regex matches for the post in the form [id] -> [matches]
		 * @param	string	$shortcode	The shortcode to check for
		 * @return	boolean
		 */
		public function has_shortcode( $shortcode ) {
			static $matches;

			/* Have we got post content ? */
			if ( ! is_object( $GLOBALS['post'] ) || ! isset( $GLOBALS['post']->post_content ) || $GLOBALS['post']->post_content === '' ) {
				return false;
			}

			$content = $GLOBALS['post']->post_content; // Current post

			/* Use WP native function if available (WP 3.6+) */
			if ( function_exists( 'has_shortcode' ) ) {
				return has_shortcode( $content, $shortcode );
			}


			/* Otherwise use adjusted copy of the native function (WP < 3.6) */
			/* Cache retrieved shortcode matches for efficiency */
			$post_id = $GLOBALS['post']->ID;
			if ( ! isset( $matches ) || ( is_array( $matches ) && ! isset( $matches[$post_id] ) ) ) {
				preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches[$post_id], PREG_SET_ORDER );
			}

			if ( empty( $matches[$post_id] ) ) {
				return false;
			}
			foreach ( $matches[$post_id] as $found ) {
				if ( $shortcode === $found[2] ) {
					return true;
				}
			}
			return false;
		}


		/**
		 * Find the uses of a shortcode within the current post
		 *
		 * @param	string	$shortcode
		 * @param	string	$content	(optional) Content to search through for the shortcode.
		 *								Defaults to the content of the current post/page/etc
		 * @return	string
		 */
		public function find_shortcode_usage( $shortcode, $content = null ) {
			$result = __( 'Not found', self::$name );

			if ( ! isset( $content) && ( ! isset( $GLOBALS['post'] ) || ! is_object( $GLOBALS['post'] ) || ! isset( $GLOBALS['post']->post_content )  ) ) {
				return $result;
			}

			if ( ! isset( $content ) ) {
				$content = $GLOBALS['post']->post_content;
			}

			$shortcode = preg_quote( $shortcode );
			$regex     = '`(?:^|[^\[])(\[' . $shortcode . '[^\]]*\])(?:.*?(\[/' . $shortcode . '\])(?:[^\]]|$))?`s';
			$count     = preg_match_all( $regex, $content, $matches, PREG_SET_ORDER );


			if ( $count > 0 ) {
				// Only one result, keep it simple
				if ( $count === 1 ) {
					$result = '<code>' . esc_html( $matches[0][1] );
					if ( isset( $matches[0][2] ) && $matches[0][2] !== '' ) {
						$result .= '&hellip;'. esc_html( $matches[0][2] );
					}
					$result .= '</code>';
				}
				// More results, let's make it a neat list
				else {
					$result = '<ol>';

					foreach ( $matches as $match ) {
						$result .= '<li><code>' . esc_html( $match[1] );

						if ( isset( $match[2] ) && $match[2] !== '' ) {
							$result .= '&hellip;'. esc_html( $match[2] );
						}
						$result .= '</code></li>';
					}
					unset( $match );

					$result .= '</ol>';
				}
			}
			return $result;
		}


		/**
		 * Retrieve a html image tag based on a value
		 *
		 * @param	array		$alt			Array with only three allowed keys:
		 *										['true']	=>	Alt value for true image
		 *										['false']	=>	Alt value for false image
		 *										['null']	=>	Alt value for null image (status unknown)
		 * @param	bool|null	$bool			The value to base the output on, either boolean or null
		 * @param	bool		$show_false		Whether to show an image if false or to return an empty string
		 * @param	bool		$show_null		Whether to show an image if null or to return an empty string
		 * @return	string
		 */
		public function render_image_based_on_bool( $alt = array(), $bool = null, $show_false = false, $show_null = false ) {
			static $images;

			if ( ! isset( $images ) ) {
				$images = array(
					'true'		=> plugins_url( 'images/badge-circle-check-16.png', __FILE__ ),
					'false'		=> plugins_url( 'images/badge-circle-cross-16.png', __FILE__ ),
					'null'		=> plugins_url( 'images/help.png', __FILE__ ),
				);
			}

			$img = ( isset( $bool ) ? ( $bool === true ? $images['true'] : $images['false'] ) : $images['null'] );

			$alt_tag = '';
			if ( isset( $bool ) && ( $bool === true && isset( $alt['true'] ) ) ) {
				$alt_tag = $alt['true'];
			}
			else if ( isset( $bool ) && ( $bool === false && isset( $alt['false'] ) ) ) {
				$alt_tag = $alt['false'];
			}
			else if ( ! isset( $bool ) && isset( $alt['null'] ) ) {
				$alt_tag = $alt['null'];
			}
			$title_tag = ( $alt_tag !== '' ) ? ' title="' . esc_attr( $alt_tag ) . '"' : '';
			$alt_tag   = ( $alt_tag !== '' ) ? ' alt="' . esc_attr( $alt_tag ) . '"' : '';

			$return = '';
			if ( ( $bool === true || ( $bool === false && $show_false === true ) ) || ( $bool === null && $show_null === true ) ) {
				$return = '<img src="' . esc_url( $img ) . '" width="16" height="16"' . $alt_tag . $title_tag . '/>';
			}
			return $return;
		}


		/**
		 * Generate the html for a shortcode detailed info table
		 *
		 * @param	string	$shortcode		Current shortcode
		 * @param	array	$info			Shortcode info
		 * @return	string
		 */
		public function render_details_table( $shortcode, $info ) {
			$rows = array();

			if ( $info['name'] !== '' ) {
				$rows['name'] = '
								<tr>
									<th colspan="2">' . esc_html__( 'Name', self::$name ) . '</th>
									<td>' . esc_html( $info['name'] ) . '</td>
								</tr>';
			}


			if ( $info['description'] !== '' ) {
				$rows['description'] = '
								<tr>
									<th colspan="2">' . esc_html__( 'Description', self::$name ) . '</th>
									<td>' . $info['description'] . '</td>
								</tr>';
			}


			$rows['syntax'] = '
								<tr>
									<th colspan="2">' . esc_html__( 'Syntax', self::$name ) . '</th>
									<td>';

			if ( isset( $info['self_closing'] ) ) {
				$param = ( ( $info['parameters']['required'] !== array() || $info['parameters']['optional'] !== array() ) ? ' <em>[parameters]</em> ' : '' );
				if ( $info['self_closing'] === true ) {
					$rows['syntax'] .= '<code>[' . esc_html( $shortcode ) . $param . ' /]</code>';
				}
				else {
					$rows['syntax'] .= '<code>[' . esc_html( $shortcode ) . $param . '] &hellip; [/' . esc_html( $shortcode ) . ']</code>';
				}
			}
			else {
				$rows['syntax'] .= '<em>' . __( 'Unknown', self::$name ) . '</em>';
			}

			$rows['syntax'] .=	'</td>
								</tr>';


			if ( $info['info_url'] !== '' ) {
				$rows['info_url'] = '
								<tr>
									<th colspan="2">' . esc_html__( 'Info Url', self::$name ) . '</th>
									<td>' . '<a href="' . esc_url( $info['info_url'] ) . '" target="_blank" class="' . esc_attr( self::$name . '-external-link' ) . '">' . esc_html( $info['info_url'] ) . '</a> ' . '</td>
								</tr>';
			}


			if ( $info['parameters']['required'] !== array() ) {
				$rows['rp'] = '
								<tr class="' . esc_attr( self::$name . '-sc-parameters' ) . '">
									<th rowspan="' . count( $info['parameters']['required'] ) . '">' . esc_html__( 'Required parameters', self::$name ) . '</th>';
				$first   = true;
				foreach ( $info['parameters']['required'] as $pm => $explain ) {
					if ( $first !== true ) {
						$rows['rp'] .= '
								<tr>';
					}
					else {
						$first = false;
					}
					$rows['rp'] .= '
									<td>' . esc_html( $pm ) . '</td>
									<td>' . esc_html( $explain ) . '</td>
								</tr>';
				}
				unset( $pm, $explain, $first );
			}


			if ( $info['parameters']['optional'] !== array() ) {
				$rows['op'] = '
								<tr class="' . esc_attr( self::$name . '-sc-parameters' ) . '">
									<th rowspan="' . count( $info['parameters']['optional'] ) . '">' . esc_html__( 'Optional parameters', self::$name ) . '</th>';
				$first   = true;
				foreach ( $info['parameters']['optional'] as $pm => $explain ) {
					if ( $first !== true ) {
						$rows['op'] .= '
								<tr>';
					}
					else {
						$first = false;
					}
					$rows['op'] .= '
									<td>' . esc_html( $pm ) . '</td>
									<td>' . esc_html( $explain ) . '</td>
								</tr>';
				}
				unset( $pm, $explain, $first );
			}


			/* Ignore the result if syntax is the only info row (as it's always there) */
			if ( 1 >= count( $rows ) && isset( $rows['syntax'] ) ) {
				$output = '';
			}
			else {
				$output = '
								<h4>' . __( 'Shortcode details', self::$name ) . '</h4>
								<table>' . implode( $rows ) . '
								</table>';
			}
			return $output;
		}




		/* ************** METHODS TO RETRIEVE SHORTCODE INFO ************** */

		/**
		 * Try and enrich the shortcode with additional information
		 *
		 * @param	string	$shortcode	Current shortcode
		 * @return	array				Shortcode info
		 */
		public function retrieve_shortcode_info( $shortcode ) {
			$info = $this->info_defaults;
			$info = apply_filters( 'db_shortcodes_info', $info, $shortcode );
			$info = apply_filters( 'db_shortcodes_info_' . $shortcode, $info );
			$info = $this->validate_shortcode_info( $info );
			return $info;
		}


		/**
		 * Get potentially provided info for a shortcode in the lhr shortcode plugin format
		 *
		 * @param	array	$info		Shortcode info
		 * @param	string	$shortcode	Current shortcode
		 * @return	array				Updated shortcode info
		 */
		public function parse_lhr_shortcode_info( $info, $shortcode ) {
			/* If the current shortcode is one of the wp standards, don't use the lhr info
			   as the info in this plugin is better */
			if ( in_array( $shortcode, $this->wp_shortcodes, true ) || has_filter( 'sim_' . $shortcode ) === false ) {
				return $info;
			}

			$lhr_defaults = array(
				'scTag'		=> $shortcode,
				'scName'	=> $shortcode,
				'scDesc'	=> __( 'No information available', self::$name ),
				'scSelfCls'	=> 'u', //unknown
				'scReqP'	=> array(),
				'scOptP'	=> array(),
			);
			$lhr_info = apply_filters( 'sim_' . $shortcode, $lhr_defaults );

			$additional = array();
			if ( is_string( $lhr_info['scName'] ) && $lhr_info['scName'] !== $lhr_defaults['scName'] ) {
				$additional['name'] = $lhr_info['scName'];
			}

			if ( is_string( $lhr_info['scDesc'] ) && $lhr_info['scDesc'] !== $lhr_defaults['scDesc'] ) {
				$additional['description'] = $lhr_info['scDesc'];
			}

			if ( is_bool( $lhr_info['scSelfCls'] ) ) {
				$additional['self_closing'] = $lhr_info['scSelfCls'];
			}

			if ( is_array( $lhr_info['scReqP'] ) && $lhr_info['scReqP'] !== array() ) {
				$additional['parameters']['required'] = $lhr_info['scReqP'];
			}

			if ( is_array( $lhr_info['scOptP'] ) && $lhr_info['scOptP'] !== array() ) {
				$additional['parameters']['optional'] = $lhr_info['scOptP'];
			}

			return self::array_merge_recursive_distinct( $info, $additional );
		}


		/**
		 * Conditionally retrieve additional info about a shortcode using Reflection on the function/method
		 * @internal Extra method so as to enable this functionality to be used in a filter and not just from ajax, but as it is expensive to check the url this way, ajax is preferred
		 *
		 * @param	array	$info		Shortcode info
		 * @param	string	$shortcode	Current shortcode
		 * @return	array				Updated shortcode info
		 */
		public function reflection_retrieve_shortcode_info( $info, $shortcode ) {
			if ( $info !== $this->info_defaults ) {
				// We already have enriched info, no need for expensive operations
				return $info;
			}
			else {
				$additional = $this->retrieve_shortcode_info_from_file( $info, $shortcode );
				return self::array_merge_recursive_distinct( $info, $additional );
			}
		}


		/**
		 * Try and retrieve additional information about a shortcode using Reflection on the function/method
		 *
		 * @param	array	$info		Shortcode info
		 * @param	string	$shortcode	Current shortcode
		 * @return	array				Updated shortcode info
		 */
		public function retrieve_shortcode_info_from_file( $info, $shortcode ) {
			$shortcodes = $GLOBALS['shortcode_tags'];

			if ( ! isset( $shortcodes[$shortcode] ) ) {
				// Not a registered shortcode
				return $info;
			}

			$callback = $shortcodes[$shortcode];

			if ( ! is_string( $callback ) && ( ! is_array( $callback ) || ( is_array( $callback ) && ( ! is_string( $callback[0] ) && ! is_object( $callback[0] ) ) ) ) ) {
				// Not a valid callback
				return $info;
			}


			/* Set up reflection */
			if ( is_string( $callback ) && strpos( $callback, '::' ) === false ) {
				$reflection = new ReflectionFunction( $callback );
			}
			else if ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
				$reflection = new ReflectionMethod( $callback );
			}
			else if ( is_array( $callback ) ) {
				$reflection = new ReflectionMethod( $callback[0], $callback[1] );
			}


			if ( ! isset( $reflection ) || $reflection->isUserDefined() === false ) {
				// Not a user defined callback, i.e. native PHP, nothing to find out about it (shouldn't ever happen)
				return $info;
			}

			$info['description']  = nl2br( $this->strip_comment_markers( $reflection->getDocComment() ) );
			$info['self_closing'] = true;
			if ( $reflection->getNumberOfRequiredParameters() > 1 ) {
				$info['self_closing'] = false;
			}
			$info['info_url'] = $this->get_plugin_url_from_file( $reflection->getFileName(), $shortcode );

			return $info;
		}


		/**
		 * Strip all comment markings and extra whitespace from a comment string
		 *
		 * Strips for each line of the comment:
		 * - '/*[*]', '//', '#', '*' from the beginning of a line
		 * - '*\/' from the end of a line
		 * - spaces and tabs from the beginning of a line
		 * - carriage returns (\r) from the end of a line
		 * - merges any combination of spaces and tabs into one space
		 *
		 * @param   string  $comment
		 * @return  string
		 */
		public function strip_comment_markers( $comment ) {
			static $search  = array( '`(^[\s]*(/\*+[\s]*(?:\*[ \t]*)?)|[\s]*(\*+/)[\s]*$|^[\s]*(\*+[ \t]*)|^[\s]*(/{2,})[\s]*|^[\s]*(#+)[\s]*)`m', '`^([ \t]+)`m', '`(\r)+[\n]?$`m', '`([ \t\r]{2,})`' );
			static $replace = array( '', '', '', ' ' );

			// Parse out all the line endings and comment delimiters
			$comment = trim( preg_replace( $search, $replace, trim( $comment ) ) );
			return $comment;
		}


		/**
		 * Get the URL where you can find more information about the shortcode.
		 *
		 * Inspired by Shortcode reference, heavily adjusted to work more accurately
		 * Source: http://wordpress.org/plugins/shortcode-reference/
		 *
		 * @param   string  $path_to_file
		 * @param   string  $shortcode
		 * @return  string  url
		 */
		public function get_plugin_url_from_file( $path_to_file, $shortcode ) {

			/* Make sure the paths use the same slashing to make them comparable */
			$path_to_file       = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path_to_file );
			$wp_abs_path        = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, ABSPATH );
			$wp_includes_path   = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, ABSPATH . WPINC ) . DIRECTORY_SEPARATOR;
			$wp_plugins_path    = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, WP_PLUGIN_DIR ) . DIRECTORY_SEPARATOR;
			$wp_mu_plugins_path = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, WPMU_PLUGIN_DIR ) . DIRECTORY_SEPARATOR;


			/* Check what type of file this is */
			if ( strpos( $path_to_file, $wp_includes_path ) !== false ) {
				// WP native
				return 'http://codex.wordpress.org/index.php?title=Special:Search&search=' . urlencode( $shortcode ) . '_Shortcode';
			}


			$is_plugin       = strpos( $path_to_file, $wp_plugins_path );
			$is_mu_plugin    = strpos( $path_to_file, $wp_mu_plugins_path );
			$plugin_data     = array();
			$plugin_basename = '';

			/* Is this a plugin in the normal plugin directory ? */
			if ( $is_plugin !== false ) {
				// Plugin in the plugins directory
				$relative_path = substr( $path_to_file, ( $is_plugin + strlen( $wp_plugins_path ) ) );

				if ( function_exists( 'get_plugins' ) && strpos( $relative_path, DIRECTORY_SEPARATOR ) !== false ) {
					// Subdirectory plugin
					$folder  = substr( $relative_path, 0, strpos( $relative_path, DIRECTORY_SEPARATOR ) );
					$folder  = DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
					$plugins = get_plugins( $folder );

					/* We'd expect only one file in the directory to have plugin data, otherwise we have
					   a problem as we won't know which file is the parent of the one containing the shortcode */
					if ( is_array( $plugins ) && count( $plugins ) === 1 ) {
						// Only one item, but we don't know the key, use foreach to set the variables we need
						foreach ( $plugins as $plugin_basename => $plugin_data ) {
							break;
						}
					}
					unset( $plugins, $folder );
				}
				else if ( function_exists( 'get_plugin_data' ) ) {
					// File directly in the plugins dir, just get straight plugin_data
					$plugin_basename = $relative_path;
					$plugin_data     = get_plugin_data( $path_to_file, false, false );
				}
				unset( $relative_path );
			}
			/* Is this a plugin in the mu plugin directory ? */
			// get_plugin_data only available on admin side
			else if ( function_exists( 'get_plugin_data' ) && $is_mu_plugin !== false ) {
				$relative_path = substr( $path_to_file, ( $is_mu_plugin + strlen( $wp_mu_plugins_path ) ) );

				if ( strpos( $relative_path, DIRECTORY_SEPARATOR ) !== false ) {
					// Subdirectory file, presume the mu-dir plugin bootstrap file is called directory-name.php
					$relative_path = substr( $relative_path, 0, strpos( $relative_path, DIRECTORY_SEPARATOR ) ) . '.php';
				}
				$plugin_basename = $relative_path;
				$plugin_data     = get_plugin_data( $wp_mu_plugins_path . $relative_path, false, false );
				unset( $relative_path );
			}

			/* Let's see if we've got some results */
			if ( is_array( $plugin_data ) && $plugin_data !== array() ) {
				if ( isset( $plugin_data['PluginURI'] ) && trim( $plugin_data['PluginURI'] ) !== '' ) {
					return trim( $plugin_data['PluginURI'] );
				}
				else if ( isset( $plugin_data['AuthorURI'] ) && trim( $plugin_data['AuthorURI'] ) !== '' ) {
					return trim( $plugin_data['AuthorURI'] );
				}
			}

			/* Not exited yet ? Ok, then we didn't have either or the info items, let's try another way */
			if ( $plugin_basename !== '' ) {
				$uri = $this->wp_repo_exists( $plugin_basename );
				if ( $uri !== false ) {
					return $uri;
				}
				else {
					return 'http://www.google.com/search?q=Wordpress+' . urlencode( '"' . $plugin_basename . '"' ) . '+shortcode+' . urlencode( '"' . $shortcode . '"' );
				}
			}

			/**
			 * If all else fails, Google is your friend, but let's try not to reveal our server path
			 */
			$is_wp = strpos( $path_to_file, $wp_abs_path );
			if ( $is_wp !== false ) {
				$sort_of_safe_path = substr( $path_to_file, ( $is_wp + strlen( $wp_abs_path ) ) );
				return 'http://www.google.com/search?q=Wordpress+' . urlencode( '"' . $sort_of_safe_path . '"' ) . '+shortcode+' . urlencode( '"' . $shortcode . '"' );
			}
			return 'http://www.google.com/search?q=Wordpress+shortcode+' . urlencode( '"' . $shortcode . '"' );
		}


		/**
		 * Try to check if a wp plugin repository exists for a given plugin
		 *
		 * @param	string	$plugin_basename	Plugin basename in the format dir/file.php
		 * @return	mixed						Url or false if unsuccessful
		 */
		public function wp_repo_exists( $plugin_basename ) {
			if ( ! extension_loaded( 'curl' ) || $plugin_basename === '' ) {
				// May be check using another method ? Nah, google is good enough
				return false;
			}

			/* Set up curl */
			$curl = curl_init();

			// Issue a HEAD request
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HEADER, true );
			curl_setopt( $curl, CURLOPT_NOBODY, true );
			// Follow any redirects
			$open_basedir = ini_get( 'open_basedir' );
			if ( false === $this->ini_get_bool( 'safe_mode' ) && ( ( is_null( $open_basedir ) || empty( $open_basedir ) ) || $open_basedir == 'none' ) ) {
				curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt( $curl, CURLOPT_MAXREDIRS, 5 );
			}
			unset( $open_basedir );
			// Bypass servers which refuse curl
			curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
			// Set a time-out
			curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 5 );
			curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
			// Stop as soon as an error occurs
			//curl_setopt( $this->curl, CURLOPT_FAILONERROR, true );


			/* Figure out what the repo should be called */
			if ( strpos( $plugin_basename, DIRECTORY_SEPARATOR ) ) {
				$plugin_basename = substr( $plugin_basename, 0, strpos( $plugin_basename, DIRECTORY_SEPARATOR ) );
			}
			if ( strpos( $plugin_basename, '.php' ) ) {
				$plugin_basename = substr( $plugin_basename, 0, strpos( $plugin_basename, '.php' ) );
			}

			/* Check if it exists */
			if ( $plugin_basename !== '' ) {
				$plugin_uri = 'http://wordpress.org/plugins/' . urlencode( $plugin_basename );

				/* Get the http headers for the given url */
				curl_setopt( $curl, CURLOPT_URL, $plugin_uri );
				$header = curl_exec( $curl );

				/* If we didn't get an error, interpret the headers */
				if ( ( false !== $header && ! empty( $header ) ) && ( 0 === curl_errno( $curl ) ) ) {
					/* Get the http status */
					$statuscode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
					if ( false === $statuscode && preg_match( '/^HTTP\/1\.[01] (\d\d\d)/', $header, $matches ) ) {
						$statuscode = (int) $matches[1];
					}

					/* No http error response, so presume valid uri */
					if ( 400 > $statuscode ) {
						curl_close( $curl );
						return $plugin_uri;
					}
				}
			}

			curl_close( $curl );
			return false;
		}


		/**
		 * Validate the shortcode info before using it to make sure that it's still in a usable form
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Validated shortcode info
		 */
		public function validate_shortcode_info( $info ) {
			$clean = $this->info_defaults;

			foreach ( $clean as $key => $value ) {
				switch ( $key ) {
					case 'name':
					case 'description':
						if ( isset( $info[$key] ) && is_string( $info[$key] ) && trim( $info[$key] ) !== '' ) {
							$clean[$key] = sanitize_text_field( trim( $info[$key] ) );
						}
						break;

					case 'self_closing':
						if ( isset( $info[$key] ) ) {
							$clean[$key] = filter_var( $info[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
						}
						break;

					case 'parameters':
						if ( isset( $info[$key] ) && is_array( $info[$key] ) && $info[$key] !== array() ) {
							foreach ( $clean[$key] as $k => $v ) {
								if ( isset( $info[$key][$k] ) && is_array( $info[$key][$k] ) && $info[$key][$k] !== array() ) {
									foreach ( $info[$key][$k] as $attr => $explanation ) {
										if ( ( ( is_string( $attr ) && trim( $attr ) !== '' ) || ( is_int( $attr ) && $attr >= 0 ) ) && ( is_string( $explanation ) && trim( $explanation ) !== '' ) ) {
											$clean[$key][$k][sanitize_key( trim( $attr ) )] = sanitize_text_field( trim( $explanation ) );
										}
									}
									unset( $attr, $explanation );
								}
							}
							unset( $k, $v );
						}
						break;
						
					case 'info_url':
						if ( ( isset( $info[$key] ) && is_string( $info[$key] ) && preg_match( '`http(s?)://(.+)`i', $info[$key] ) ) ) {
							$clean[$key] = esc_url_raw( trim( $info[$key] ) );
						}
						break;
				};
			}
			return $clean;
		}




		/* ************** METHODS TO HANDLE AJAX REQUESTS ************** */

		/**
		 * Try and retrieve more information about the shortcode from the actual php code
		 *
		 * @param   string  $shortcode  Validated shortcode
		 * @return  void
		 */
		function ajax_retrieve_details( $shortcode ) {
			$info = $this->info_defaults;
			$info = $this->reflection_retrieve_shortcode_info( $info, $shortcode );

			if ( $info === $this->info_defaults ) {
				$response = array(
					'id'    => 0,
					'data'  => '',
				);
				$this->send_ajax_response( $response );
				exit;
			}

			$response = array(
				'id'        => 1,
				'data'      => $this->render_details_table( $shortcode, $info ),
				'tr_class'  => self::$name . '-details',
			);
			if ( isset( $info['info_url'] ) && $info['info_url'] !== '' ) {
				$response['supplemental'] = $this->render_view_online_link( $info['info_url'] );
			}

			$this->send_ajax_response( $response );
			exit;
		}


		/**
		 * Find out if a shortcode is used anywhere
		 *
		 * Liberally nicked from TR All Shortcodes plugin & adjusted based on WP posts-list-table code
		 * Source: http://wordpress.org/plugins/tr-all-shortcodes/
		 * Source: http://core.trac.wordpress.org/browser/trunk/src/wp-admin/includes/class-wp-posts-list-table.php#L473
		 *
		 * @param   string  $shortcode  Validated shortcode
		 * @return  void
		 */
		function ajax_find_shortcode_uses( $shortcode ) {

			// '_' is a wildcard in mysql, so escape it
			$query = $GLOBALS['wpdb']->prepare(
				'select * from `' . $GLOBALS['wpdb']->posts . '`
					where `post_status` <> "inherit"
						and `post_type` <> "attachment"
						and `post_content` like %s
					order by `post_type` ASC, `post_date` DESC;',
				'%[' . str_replace( '_', '\_', $shortcode ) . '%'
			);
			$posts = $GLOBALS['wpdb']->get_results( $query );


			/* Do we have posts ? */
			if ( $GLOBALS['wpdb']->num_rows === 0 ) {
				$response = array(
					'id'    => 0,
					'data'  => '',
				);
				$this->send_ajax_response( $response );
				exit;
			}


			/* Ok, we've found some posts using the shortcode */
			$output = '
						<h4>' . __( 'Shortcode found in the following posts/pages/etc:', self::$name ) . '</h4>
						<table>
							<thead>
								<tr>
									<th>#</th>' .
									/* TRANSLATORS: no need to translate, WP standard translation will be used */ '
									<th>' . esc_html__( 'Title' ) . '</th>
									<th>' . esc_html__( 'Post Type', self::$name ) . '</th>
									<th>' . esc_html__( 'Status' ) . '</th>' .
									/* TRANSLATORS: no need to translate, WP standard translation will be used */ '
									<th>' . esc_html__( 'Author' ) . '</th>
									<th>' . esc_html__( 'Shortcode usage(s)', self::$name ) . '</th>
								</tr>
							</thead>
							<tbody>';


			foreach ( $posts as $i => $post ) {
				$edit_link        = get_edit_post_link( $post->ID );
				$title            = _draft_or_post_title( $post->ID );
				$post_type_object = get_post_type_object( $post->post_type );
				$can_edit_post    = current_user_can( 'edit_post', $post->ID );

				switch ( $post->post_status ) {
					case 'publish':
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$post_status = __( 'Published' );
						break;
					case 'future':
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$post_status = __( 'Scheduled' );
						break;
					case 'private':
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$post_status = __( 'Private' );
						break;
					case 'pending':
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$post_status = __( 'Pending Review' );
						break;
					case 'draft':
					case 'auto-draft':
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$post_status = __( 'Draft' );
						break;
					case 'trash':
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$post_status = __( 'Trash' );
						break;
					default:
						$post_status = __( 'Unknown', self::$name );
						break;
				}

				$actions = array();
				if ( $can_edit_post && 'trash' != $post->post_status ) {
					/* TRANSLATORS: no need to translate, WP standard translation will be used */
					$actions['edit'] = '<a href="' . $edit_link . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">';
					/* TRANSLATORS: no need to translate, WP standard translation will be used */
					$actions['edit'] .= __( 'Edit' ) . '</a>';
				}
				if ( $post_type_object->public ) {
					if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
						if ( $can_edit_post ) {
							/* TRANSLATORS: no need to translate, WP standard translation will be used */
							$actions['view'] = '<a href="' . esc_url( apply_filters( 'preview_post_link', set_url_scheme( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">';
							/* TRANSLATORS: no need to translate, WP standard translation will be used */

							$actions['view'] .= __( 'Preview' ) . '</a>';

						}
					}
					else if ( 'trash' != $post->post_status ) {
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">';
						/* TRANSLATORS: no need to translate, WP standard translation will be used */
						$actions['view'] .= __( 'View' ) . '</a>';
					}
				}


				$output .= '
								<tr>
									<td>' . ( $i + 1 ) . '</td>
									<td class="column-title"><strong>' . $title . '</strong>';

				if ( $actions !== array() ) {
					$output .= '<div class="row-actions">' . implode( ' | ', $actions ) . '</div>';
				}

				$output .= '
									</td>
									<td>' . esc_html( $post_type_object->labels->singular_name ) . '</td>
									<td>' . esc_html( $post_status ) . '</td>
									<td>' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</td>
									<td>' . $this->find_shortcode_usage( $shortcode, $post->post_content ) . '</td>
								</tr>';
			}
			unset( $i, $post );

			$output .= '
							</tbody>
						</table>';


			$response = array(
				'id'        => 1,
				'data'      => $output,
				'tr_class'  => self::$name . '-uses',
			);
			$this->send_ajax_response( $response );
			exit;
		}


		/**
		 * Send ajax response
		 *
		 * @param   array   $response   Part response in the format:
		 *                              [id]        	=> 0 = no result, 1 = result
		 *                              [data]      	=> html string (can be empty if no result)
 		 *                              [supplemental]  => (optional) supplemental info to pass
		 *                              [tr_class]  	=> (optional) class for the wrapping row
		 * @return  void
		 */
		public function send_ajax_response( $response ) {
			$tr_class = '';
			if ( isset( $response['tr_class'] ) && $response['tr_class'] !== '' ) {
				$tr_class = ' class="' . esc_attr( $response['tr_class'] ) . '"';
			}

			$data = '';
			if ( $response['data'] !== '' ) {
				$data = '<tr' . $tr_class . '>
							<td>&nbsp;</td>
							<td colspan="{colspan}">
								' . $response['data'] . '
							</td>
						</tr>';
			}

			$supplemental = array();
			// Only accounts for the expected new view online link, everything else will be buggered
			if ( isset( $response['supplemental'] ) && $response['supplemental'] !== '' ) {
				$supplemental['url_link'] = ' | ' . $response['supplemental'];
			}

			/* Send the response */
			$ajax_response = new WP_Ajax_Response();
			$ajax_response->add(
				array(
					'what'			=> self::$name,
					'action'		=> $_POST['action'],
					'id'			=> $response['id'],
					'data'			=> $data,
					'supplemental'	=> $supplemental,
				)
			);
			$ajax_response->send();
			exit;
		}




		/* ************** HELPER METHODS ************** */

		/**
		 * Check if a callback is a closure
		 *
		 * @param   mixed	$arg	Function name
		 * @return  boolean
		 */
		public function is_closure( $arg ) {
			if( version_compare( PHP_VERSION, '5.3', '<' ) ) {
				return false;
			}

		    include_once( plugin_dir_path( __FILE__ ) . 'php5.3-closure-test.php' );
		    return debug_bar_shortcodes_is_closure( $arg );
		}


		/**
		 * Test a boolean PHP ini value
		 *
		 * @since 3.0
		 * @param string	$a	key of the value you want to get
		 * @return bool
		 */
		private function ini_get_bool( $a ) {
			$b = ini_get( $a );

			switch ( strtolower( $b ) ) {
				case 'on':
				case 'yes':
				case 'true':
					return 'assert.active' !== $a;

				default:
					return (bool) (int) $b;
			}
		}


		/**
		 * Recursively merge a variable number of arrays, using the left array as base,
		 * giving priority to the right array.
		 *
		 * Difference with native array_merge_recursive():
		 * array_merge_recursive converts values with duplicate keys to arrays rather than
		 * overwriting the value in the first array with the duplicate value in the second array.
		 *
		 * array_merge_recursive_distinct does not change the data types of the values in the arrays.
		 * Matching keys' values in the second array overwrite those in the first array, as is the
		 * case with array_merge.
		 *
		 * Freely based on information found on http://www.php.net/manual/en/function.array-merge-recursive.php
		 *
		 * @param	array	2 or more arrays to merge
		 * @return	array
		 */
		public static function array_merge_recursive_distinct() {

			$arrays = func_get_args();
			if ( count( $arrays ) < 2 ) {
				if ( $arrays === array() ) {
					return array();
				}
				else {
					return $arrays[0];
				}
			}

			$merged = array_shift( $arrays );

			foreach ( $arrays as $array ) {
				foreach ( $array as $key => $value ) {
					if ( is_array( $value ) && ( isset( $merged[$key] ) && is_array( $merged[$key] ) ) ) {
						$merged[$key] = self::array_merge_recursive_distinct( $merged[$key], $value );
					}
					else {
						$merged[$key] = $value;
					}
				}
				unset( $key, $value );
			}
			return $merged;
		}




		/* ************** METHODS TO ENRICH INFO ABOUT WP STANDARD SHORTCODES ************** */

		/**
		 * Enrich the information for the standard WP [audio] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_audio( $info ) {
			$additional = array(
				'name'			=> __( 'Audio Media', Debug_Bar_Shortcodes::DBS_NAME ),
				'description'	=> __( 'The Audio feature allows you to embed audio files and play them back. This was added as of WordPress 3.6.', Debug_Bar_Shortcodes::DBS_NAME ),
				'self_closing'	=> true,
				'parameters'	=> array(
					'optional'		=> array(
						'src'			=> __( 'The source of your audio file. If not included it will auto-populate with the first audio file attached to the post.', Debug_Bar_Shortcodes::DBS_NAME ),
						'mp3'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'mp3' ),
						'm4a'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'm4a' ),
						'ogg'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'ogg' ),
						'wav'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'wav' ),
						'wma'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'wma' ),
						'loop'			=> __( 'Allows for the looping of media. Defaults to "off".', Debug_Bar_Shortcodes::DBS_NAME ),
						'autoplay'		=> __( 'Causes the media to automatically play as soon as the media file is ready. Defaults to "off".', Debug_Bar_Shortcodes::DBS_NAME ),
						'preload'		=> __( 'Specifies if and how the audio should be loaded when the page loads. Defaults to "none".', Debug_Bar_Shortcodes::DBS_NAME ),
					),
				),
				'info_url'		=> 'http://codex.wordpress.org/Audio_Shortcode',
			);
			return self::array_merge_recursive_distinct( $info, $additional );
		}


		/**
		 * Enrich the information for the standard WP [video] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_video( $info ) {
			$additional = array(
				'name'			=> __( 'Video Media', Debug_Bar_Shortcodes::DBS_NAME ),
				'description'	=> __( 'The Video feature allows you to embed video files and play them back. This was added as of WordPress 3.6.', Debug_Bar_Shortcodes::DBS_NAME ),
				'self_closing'	=> true,
				'parameters'	=> array(
					'required'		=> array(
						'height'		=> sprintf( __( 'Defines %s of the media. Value is automatically detected on file upload.', Debug_Bar_Shortcodes::DBS_NAME ), __( 'height', self::$name ) ),
						'width'			=> sprintf( __( 'Defines %s of the media. Value is automatically detected on file upload.', Debug_Bar_Shortcodes::DBS_NAME ), __( 'width', self::$name ) ),
					),
					'optional'		=> array(
						'src'			=> __( 'The source of your video file. If not included it will auto-populate with the first video file attached to the post.', Debug_Bar_Shortcodes::DBS_NAME ),
						'mp4'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'mp4' ),
						'm4v'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'm4v' ),
						'webm'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'webm' ),
						'ogv'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'ogv' ),
						'wmv'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'wmv' ),
						'flv'			=> sprintf( __( 'Source of %s fallback file', Debug_Bar_Shortcodes::DBS_NAME ), 'fla' ),
						'poster'		=> __( 'Defines image to show as placeholder before the media plays. Defaults to "none".', Debug_Bar_Shortcodes::DBS_NAME ),
						'loop'			=> __( 'Allows for the looping of media. Defaults to "off"', Debug_Bar_Shortcodes::DBS_NAME ),
						'autoplay'		=> __( 'Causes the media to automatically play as soon as the media file is ready. Defaults to "off".', Debug_Bar_Shortcodes::DBS_NAME ),
						'preload'		=> __( 'Specifies if and how the video should be loaded when the page loads. Defaults to "metadata".', Debug_Bar_Shortcodes::DBS_NAME ),
					),
				),
				'info_url'		=> 'http://codex.wordpress.org/Video_Shortcode',
			);
			return self::array_merge_recursive_distinct( $info, $additional );
		}


		/**
		 * Enrich the information for the standard WP [wp_caption] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_wp_caption( $info ) {
			return $this->enrich_sc_caption( $info );
		}


		/**
		 * Enrich the information for the standard WP [caption] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_caption( $info ) {
			$additional = array(
				'name'			=> __( 'Wrap captions around content', Debug_Bar_Shortcodes::DBS_NAME ),
				'description'	=> __( 'The Caption feature allows you to wrap captions around content. This is primarily used with individual images.', Debug_Bar_Shortcodes::DBS_NAME ),
				'self_closing'	=> false,
				'parameters'	=> array(
					'required'		=> array(
						'caption'		=> __( 'The actual text of your caption.', Debug_Bar_Shortcodes::DBS_NAME ),
						'width'			=> __( 'How wide the caption should be in pixels.', Debug_Bar_Shortcodes::DBS_NAME ),
					),
					'optional'		=> array(
						'id'			=> __( 'A unique HTML ID that you can change to use within your CSS.', Debug_Bar_Shortcodes::DBS_NAME ),
						'align'			=> __( 'The alignment of the caption within the post. Valid values are: alignnone (default), aligncenter, alignright, and alignleft.', Debug_Bar_Shortcodes::DBS_NAME ),
					),
				),
				'info_url'		=> 'http://codex.wordpress.org/Caption_Shortcode',
			);
			return self::array_merge_recursive_distinct( $info, $additional );
		}


		/**
		 * Enrich the information for the standard WP [gallery] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_gallery( $info ) {
			$additional = array(
				'name'			=> __( 'Image Gallery', Debug_Bar_Shortcodes::DBS_NAME ),
				'description'	=> __( 'The Gallery feature allows you to add one or more image galleries to your posts and pages.', Debug_Bar_Shortcodes::DBS_NAME ),
				'self_closing'	=> true,
				'parameters'	=> array(
					'optional'		=> array(
						'orderby'		=> __( 'Specify how to sort the display thumbnails. The default is "menu_order".', Debug_Bar_Shortcodes::DBS_NAME ),
						'order'			=> __( 'Specify the sort order used to display thumbnails. ASC or DESC.', Debug_Bar_Shortcodes::DBS_NAME ),
						'columns'		=> __( 'Specify the number of columns. The gallery will include a break tag at the end of each row, and calculate the column width as appropriate. The default value is 3. If columns is set to 0, no row breaks will be included.', Debug_Bar_Shortcodes::DBS_NAME ),
						'id'			=> __( 'Specify the post ID. The gallery will display images which are attached to that post. The default behavior, if no ID is specified, is to display images attached to the current post.', Debug_Bar_Shortcodes::DBS_NAME ),
						'size'			=> __( 'specify the image size to use for the thumbnail display. Valid values include "thumbnail", "medium", "large", "full". The default value is "thumbnail".', Debug_Bar_Shortcodes::DBS_NAME ),
						'itemtag'		=> __( 'The name of the XHTML tag used to enclose each item in the gallery. The default is "dl".', Debug_Bar_Shortcodes::DBS_NAME ),
						'icontag'		=> __( 'The name of the XHTML tag used to enclose each thumbnail icon in the gallery. The default is "dt".', Debug_Bar_Shortcodes::DBS_NAME ),
						'captiontag'	=> __( 'The name of the XHTML tag used to enclose each caption. The default is "dd".', Debug_Bar_Shortcodes::DBS_NAME ),
						'link'			=> __( 'You can set it to "file" so each image will link to the image file. The default value links to the attachment\'s permalink.', Debug_Bar_Shortcodes::DBS_NAME ),
						'include'		=> __( 'Comma separated attachment IDs to show only the images from these attachments. ', Debug_Bar_Shortcodes::DBS_NAME ),
						'exclude'		=> __( 'Comma separated attachment IDs excludes the images from these attachments. Please note that include and exclude cannot be used together.', Debug_Bar_Shortcodes::DBS_NAME ),
					),
				),
				'info_url'		=> 'http://codex.wordpress.org/Gallery_Shortcode',
			);
			return self::array_merge_recursive_distinct( $info, $additional );
		}


		/**
		 * Enrich the information for the standard WP [embed] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_embed( $info ) {
			$additional = array(
				'name'			=> __( 'Embed videos, images, and other content', Debug_Bar_Shortcodes::DBS_NAME ),
				'description'	=> __( 'You can opt to wrap a URL in the [embed] shortcode. It will accomplish the same effect as having it on a line of it\'s own, but does not require the "Auto-embeds" setting to be enabled. It also allows you to set a maximum (but not fixed) width and height.If WordPress fails to embed your URL you will get a hyperlink to the URL.', Debug_Bar_Shortcodes::DBS_NAME ),
				'self_closing'	=> false,
				'parameters'	=> array(
					'optional'		=> array(
						'width'			=> sprintf( __( 'Maximum %s for the embedded object.', Debug_Bar_Shortcodes::DBS_NAME ), __( 'width', self::$name ) ),
						'height'		=> sprintf( __( 'Maximum %s for the embedded object.', Debug_Bar_Shortcodes::DBS_NAME ), __( 'height', self::$name ) ),
					),
				),
				'info_url'		=> 'http://codex.wordpress.org/Embed_Shortcode',
			);
			return self::array_merge_recursive_distinct( $info, $additional );
		}
		
		
		/**
		 * Enrich the information for the standard WP [playlist] shortcode
		 *
		 * @param	array	$info	Shortcode info
		 * @return	array			Updated shortcode info
		 */
		public function enrich_sc_playlist( $info ) {
			$additional = array(
				'name'			=> __( 'Media Playlist', Debug_Bar_Shortcodes::DBS_NAME ),
				'description'	=> __( 'The playlist shortcode implements the functionality of displaying a collection of WordPress audio or video files in a post using a simple Shortcode.', Debug_Bar_Shortcodes::DBS_NAME ),
				'self_closing'	=> true,
				'parameters'	=> array(
					'optional'		=> array(
						'type'			=> __( 'Type of playlist to display. Accepts "audio" or "video". Defaults to "audio".', Debug_Bar_Shortcodes::DBS_NAME ),
						'order'			=> __( 'Designates ascending or descending order of items in the playlist. Accepts "ASC", "DESC". Defaults to "ASC".', Debug_Bar_Shortcodes::DBS_NAME ),
						'orderby'		=> __( 'Any column, or columns, to sort the playlist by. Accepts "rand" to play the list in random order. Defaults to "menu_order ID". If `$ids` are passed, this defaults to the order of the $ids array (\'post__in\').', Debug_Bar_Shortcodes::DBS_NAME ),
						'id'			=> __( 'If an explicit `$ids` array is not present, this parameter will determine which attachments are used for the playlist. Defaults to the current post ID.', Debug_Bar_Shortcodes::DBS_NAME ),
						'ids'			=> __( 'Create a playlist out of these explicit attachment IDs. If empty, a playlist will be created from all `$type` attachments of `$id`.', Debug_Bar_Shortcodes::DBS_NAME ),
						'exclude'		=> __( 'List of specific attachment IDs to exclude from the playlist.', Debug_Bar_Shortcodes::DBS_NAME ),
						'style'			=> __( 'Playlist style to use. Accepts "light" or "dark". Defaults to "light".', Debug_Bar_Shortcodes::DBS_NAME ),
						'tracklist'		=> __( 'Whether to show or hide the playlist. Defaults to (bool) true.', Debug_Bar_Shortcodes::DBS_NAME ),
						'tracknumbers'	=> __( 'Whether to show or hide the numbers next to entries in the playlist. Defaults to (bool) true.', Debug_Bar_Shortcodes::DBS_NAME ),
						'images'		=> __( 'Show or hide the video or audio thumbnail (Featured Image/post thumbnail). Defaults to (bool) true.', Debug_Bar_Shortcodes::DBS_NAME ),
						'artists'		=> __( 'Whether to show or hide artist name in the playlist. Defaults to (bool) true.', Debug_Bar_Shortcodes::DBS_NAME ),
					),
				),
				'info_url'		=> 'http://codex.wordpress.org/Playlist_Shortcode',
			);
			return self::array_merge_recursive_distinct( $info, $additional );
		}

	} // End of class Debug_Bar_Shortcodes_Info
} // End of if class_exists wrapper