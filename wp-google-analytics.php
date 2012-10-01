<?php
/**
 * Plugin Name: WP Google Analytics
 * Plugin URI: http://bluedogwebservices.com/wordpress-plugin/wp-google-analytics/
 * Description: Lets you use <a href="http://analytics.google.com">Google Analytics</a> to track your WordPress site statistics
 * Version: 1.3-working
 * Author: Aaron D. Campbell
 * Author URI: http://ran.ge/
 */

define('WGA_VERSION', '1.3-working');

/*  Copyright 2006  Aaron D. Campbell  (email : wp_plugins@xavisys.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * wpGoogleAnalytics is the class that handles ALL of the plugin functionality.
 * It helps us avoid name collisions
 * http://codex.wordpress.org/Writing_a_Plugin#Avoiding_Function_Name_Collisions
 */
class wpGoogleAnalytics {

	/**
	 * @var wpGoogleAnalytics - Static property to hold our singleton instance
	 */
	static $instance = false;

	static $page_slug = 'wp-google-analytics';

	/**
	 * This is our constructor, which is private to force the use of get_instance()
	 * @return void
	 */
	private function __construct() {
		add_action( 'admin_init',               array( $this, 'admin_init' ) );
		add_action( 'admin_menu',               array( $this, 'admin_menu' ) );
		add_action( 'get_footer',               array( $this, 'insert_code' ) );
		add_action( 'wp_enqueue_scripts',       array( $this, 'track_outgoing' ) );
	}

 	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function get_instance() {
		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * This adds the options page for this plugin to the Options page
	 */
	public function admin_menu() {
		add_options_page(__('Google Analytics'), __('Google Analytics'), 'manage_options', self::$page_slug, array( $this, 'settings_view' ) );
	}

	/**
	 * Register our settings
	 */
	public function admin_init() {
		register_setting( 'wga', 'wga', array( $this, 'sanitize_general_options' ) );

		add_settings_section( 'wga_general', false, '__return_false', 'wga' );
		add_settings_field( 'code', __( 'Google Analytics tracking ID:', 'wp-google-analytics' ), array( $this, 'field_code' ), 'wga', 'wga_general' );
		add_settings_field( 'additional_items', __( 'Additional items to log:', 'wp-google-analytics' ), array( $this, 'field_additional_items' ), 'wga', 'wga_general' );
		add_settings_field( 'do_not_track', __( 'Visits to ignore:', 'wp-google-analytics' ), array( $this, 'field_do_not_track' ), 'wga', 'wga_general' );
		add_settings_field( 'custom_vars', __( 'Custom variables:', 'wp-google-analytics' ), array( $this, 'field_custom_variables' ), 'wga', 'wga_general' );
	}

	/**
	 * Where the user adds their Google Analytics code
	 */
	public function field_code() {
		echo '<input name="wga[code]" id="wga-code" type="text" value="' . esc_attr( $this->_get_options( 'code' ) ) . '" />';
		echo '<p class="description">' . __( 'Paste your Google Analytics tracking ID (e.g. "UA-XXXXXX-X") into the field.', 'wp-google-analytics' ) . '</p>';
	}

	/**
	 * Option to log additional items
	 */
	public function field_additional_items() {
		$addtl_items = array(
				'log_404s'       => __( 'Log 404 errors as /404/{url}?referrer={referrer}', 'wp-google-analytics' ),
				'log_searches'   => __( 'Log searches as /search/{search}?referrer={referrer}', 'wp-google-analytics' ),
				'log_outgoing'   => __( 'Log outgoing links as events', 'wp-google-analytics' ),
			);
		foreach( $addtl_items as $id => $label ) {
			echo '<label for="wga_' . $id . '">';
			echo '<input id="wga_' . $id . '" type="checkbox" name="wga[' . $id . ']" value="true" ' . checked( 'true', $this->_get_options( $id ), false ) . ' />';
			echo '&nbsp;&nbsp;' . $label;
			echo '</label><br />';
		}
	}

	/**
	 * Define custom variables to be included in your tracking code
	 */
	public function field_custom_variables() {
		$custom_vars = $this->_get_options( 'custom_vars' );

		$scope_options = array(
				0 => __( 'Default', 'wp-google-analytics' ),
				1 => __( 'Visitor', 'wp-google-analytics' ),
				2 => __( 'Session', 'wp-google-analytics' ),
				3 => __( 'Page', 'wp-google-analytics' ),
			);
		for ( $i = 1; $i <= 5; $i++ ) {
			$name = ( isset( $custom_vars[$i]['name'] ) ) ? $custom_vars[$i]['name'] : '';
			$value = ( isset( $custom_vars[$i]['value'] ) ) ? $custom_vars[$i]['value'] : '';
			$scope = ( isset( $custom_vars[$i]['scope'] ) ) ? $custom_vars[$i]['scope'] : 0;
			echo '<label for="wga_custom_var_' . $i . '_name"><strong>' . $i . ')</strong>&nbsp;' . __( 'Name', 'wp-google-analytics' ) . '&nbsp;';
			echo '<input id="wga_' . $i . '" type="text" name="wga[custom_vars][' . $i . '][name]" value="' . esc_attr( $name ) . '" />';
			echo '</label>&nbsp;&nbsp;';
			echo '<label for="wga_custom_var_' . $i . '_value">' . __( 'Value', 'wp-google-analytics' ) . '&nbsp;';
			echo '<input id="wga_' . $i . '" type="text" name="wga[custom_vars][' . $i . '][value]" value="' . esc_attr( $value ) . '" />';
			echo '</label>&nbsp;&nbsp;';
			echo '<label for="wga_custom_var_' . $i . '_scope">' . __( 'Scope', 'wp-google-analytics' ) . '&nbsp;';
			echo '<select id="wga_custom_var_' . $i . '_scope" name="wga[custom_vars][' . $i . '][scope]">';
			foreach( $scope_options as $key => $label ) {
				echo '<option value="' . $key . '" ' . selected( $scope, $key, false ) . '>';
				echo $label . '</option>';
			}
			echo '</select>';
			echo '</label><br />';
		}
	}

	public function field_do_not_track() {
		$do_not_track = array(
				'ignore_admin_area'       => __( 'Do not log anything in the admin area', 'wp-google-analytics' ),
			);
		global $wp_roles;
		foreach( $wp_roles->roles as $role => $role_info ) {
			$do_not_track['ignore_role_' . $role] = sprintf( __( 'Do not log %s when logged in', 'wp-google-analytics' ), rtrim( $role_info['name'], 's' ) );
		}
		foreach( $do_not_track as $id => $label ) {
			echo '<label for="wga_' . $id . '">';
			echo '<input id="wga_' . $id . '" type="checkbox" name="wga[' . $id . ']" value="true" ' . checked( 'true', $this->_get_options( $id ), false ) . ' />';
			echo '&nbsp;&nbsp;' . $label;
			echo '</label><br />';
		}
	}

	/**
	 * Sanitize all of the options associated with the plugin
	 */
	public function sanitize_general_options( $in ) {

		$out = array();

		// The actual tracking ID
		if ( preg_match( '#UA-[\d-]+#', $in['code'], $matches ) )
			$out['code'] = $matches[0];
		else
			$out['code'] = '';

		$checkbox_items = array(
				// Additional items you can track
				'log_404s',
				'log_searches',
				'log_outgoing',
				// Things to ignore
				'ignore_admin_area',
			);
		global $wp_roles;
		foreach( $wp_roles->roles as $role => $role_info ) {
			$checkbox_items[] = 'ignore_role_' . $role;
		}
		foreach( $checkbox_items as $checkbox_item ) {
			if ( isset( $in[$checkbox_item] ) && 'true' == $in[$checkbox_item] )
				$out[$checkbox_item] = 'true';
			else
				$out[$checkbox_item] = 'false';
		}

		// Custom variables
		for( $i = 1; $i <= 5; $i++ ) {
			foreach( array( 'name', 'value', 'scope' ) as $key ) {
				if ( isset( $in['custom_vars'][$i][$key] ) )
					$out['custom_vars'][$i][$key] = sanitize_text_field( $in['custom_vars'][$i][$key] );
				else
					$out['custom_vars'][$i][$key] = '';
			}
		}

		return $out;
	}

	/**
	 * This is used to display the options page for this plugin
	 */
	public function settings_view() {
?>
		<div class="wrap">
			<h2><?php _e('Google Analytics Options') ?></h2>
			<form action="options.php" method="post" id="wp_google_analytics">
				<?php
					settings_fields( 'wga' );
					do_settings_sections( 'wga' );
					submit_button( __( 'Update Options', 'wp-google-analytics' ) );
				?>
			</form>
		</div>
<?php
	}

	/**
	 * Used to generate a tracking URL
	 *
	 * @param array $track - Must have ['data'] and ['code']
	 * @return string - Tracking URL
	 */
	private function _get_url($track) {
		$site_url = ( is_ssl() ? 'https://':'http://' ).$_SERVER['HTTP_HOST'];
		foreach ($track as $k=>$value) {
			if (strpos(strtolower($value), strtolower($site_url)) === 0) {
				$track[$k] = substr($track[$k], strlen($site_url));
			}
			if ($k == 'data') {
				$track[$k] = preg_replace("/^https?:\/\/|^\/+/i", "", $track[$k]);
			}

			//This way we don't lose search data.
			if ($k == 'data' && $track['code'] == 'search') {
				$track[$k] = urlencode($track[$k]);
			} else {
				$track[$k] = preg_replace("/[^a-z0-9\.\/\+\?=-]+/i", "_", $track[$k]);
			}

			$track[$k] = trim($track[$k], '_');
		}
		$char = (strpos($track['data'], '?') === false)? '?':'&amp;';
		return str_replace("'", "\'", "/{$track['code']}/{$track['data']}{$char}referer=" . urlencode( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '' ) );
	}

	/**
	 * Maybe output or return, depending on the context
	 */
	private function _output_or_return( $val, $maybe ) {
		if ( $maybe )
			echo $val . "\r\n";
		else
			return $val;
	}

	/**
	 * This injects the Google Analytics code into the footer of the page.
	 *
	 * @param bool[optional] $output - defaults to true, false returns but does NOT echo the code
	 */
	public function insert_code( $output = true ) {
		//If $output is not a boolean false, set it to true (default)
		$output = ($output !== false);

		$tracking_id = $this->_get_options( 'code' );
		if ( empty( $tracking_id ) )
			return $this->_output_or_return( '<!-- Your Google Analytics Plugin is missing the tracking ID -->', $output );

		//get our plugin options
		$wga = $this->_get_options();
		//If the user's role has wga_no_track set to true, return without inserting code
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$role = array_shift( $current_user->roles );
			if ( 'true' == $this->_get_options( 'ignore_role_' . $role ) )
				return $this->_output_or_return( "<!-- Google Analytics Plugin is set to ignore your user role -->", $output );
		}

		//If $admin is true (we're in the admin_area), and we've been told to ignore_admin_area, return without inserting code
		if (is_admin() && (!isset($wga['ignore_admin_area']) || $wga['ignore_admin_area'] != 'false'))
			return $this->_output_or_return( "<!-- Your Google Analytics Plugin is set to ignore Admin area -->", $output );

		$custom_vars = array(
			"_gaq.push(['_setAccount', '{$tracking_id}']);",
		);

		$track = array();
		if (is_404() && (!isset($wga['log_404s']) || $wga['log_404s'] != 'false')) {
			//Set track for 404s, if it's a 404, and we are supposed to
			$track['data'] = $_SERVER['REQUEST_URI'];
			$track['code'] = '404';
		} elseif (is_search() && (!isset($wga['log_searches']) || $wga['log_searches'] != 'false')) {
			//Set track for searches, if it's a search, and we are supposed to
			$track['data'] = $_REQUEST['s'];
			$track['code'] = "search";
		}

		if ( ! empty( $track ) ) {
			$track['url'] = $this->_get_url( $track );
			//adjust the code that we output, account for both types of tracking
			$track['url'] = esc_js( str_replace( '&', '&amp;', $track['url'] ) );
			$custom_vars[] = "_gaq.push(['_trackPageview','{$track['url']}']);";
		} else {
			$custom_vars[] = "_gaq.push(['_trackPageview']);";
		}

		// Add custom variables specified by the user
		foreach( $this->_get_options( 'custom_vars', array() ) as $i => $custom_var ) {
			if ( empty( $custom_var['name'] ) || empty( $custom_var['value'] ) )
				continue;
			$atts = array(
					"'_setCustomVar'",
					intval( $i ),
					"'" . esc_js( $custom_var['name'] ) . "'",
					"'" . esc_js( $custom_var['value'] ) . "'",
				);
			if ( $custom_var['scope'] )
				$atts[] = intval( $custom_var['scope'] );
			$custom_vars[] = "_gaq.push([" . implode( ', ', $atts ) . "]);";
		}

		$async_code = "<script type='text/javascript'>
	var _gaq = _gaq || [];
	%custom_vars%

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>";
		$custom_vars_string = implode( "\r\n", $custom_vars );
		$async_code = str_replace( '%custom_vars%', $custom_vars_string, $async_code );

		return $this->_output_or_return( $async_code, $output );

	}

	/**
	 * Used to get one or all of our plugin options
	 *
	 * @param string[optional] $option - Name of options you want.  Do not use if you want ALL options
	 * @return array of options, or option value
	 */
	private function _get_options( $option = null, $default = false ) {

		$o = get_option('wga');

		if (isset($option)) {

			if (isset($o[$option])) {
				if ( 'code' == $option ) {
					if ( preg_match( '#UA-[\d-]+#', $o[$option], $matches ) )
						return $matches[0];
					else
						return '';
				} else
					return $o[$option];
			} else {
				if ( 'ignore_role_' == substr( $option, 0, 12 ) ) {
					global $wp_roles;
					// Backwards compat for when the tracking information was stored as a cap
					$maybe_role = str_replace( 'ignore_role_', '', $option );
					if ( isset( $wp_roles->roles[$maybe_role] ) ) {
						if ( isset( $wp_roles->roles[$maybe_role]['capabilities']['wga_no_track'] ) && $wp_roles->roles[$maybe_role]['capabilities']['wga_no_track'] )
							return 'true';
					}
					return false;
				}
				return $default;
			}
		} else {
			return $o;
		}
	}

	/**
	 * If we track outgoing links, this will enqueue our javascript file
	 */
	public function track_outgoing() {
		$wga = $this->_get_options();
		if ( 'true' == $wga['log_outgoing'] && (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) && ( ! is_admin() || $wga['ignore_admin_area'] == 'false') )
			wp_enqueue_script( 'wp-google-analytics', plugin_dir_url( __FILE__ ) . 'wp-google-analytics.js', array( 'jquery' ), '0.0.3' );
	}

}

global $wp_google_analytics;
$wp_google_analytics = wpGoogleAnalytics::get_instance();
