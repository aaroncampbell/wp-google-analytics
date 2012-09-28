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

//If this is set to true, extra info will be dumped to the browser.
//ONLY do this if you really need it
define('WGA_DEBUG', false);

/**
 * wpGoogleAnalytics is the class that handles ALL of the plugin functionality.
 * It helps us avoid name collisions
 * http://codex.wordpress.org/Writing_a_Plugin#Avoiding_Function_Name_Collisions
 */
class wpGoogleAnalytics {

	static $page_slug = 'wp-google-analytics';

	/**
	 * Initialize the plugin
	 */
	function __construct() {
		add_action( 'admin_init',               array( $this, 'admin_init' ) );
		add_action( 'admin_menu',               array( $this, 'admin_menu' ) );
		add_action( 'get_footer',               array( $this, 'insert_code' ) );
		add_action( 'init',                     array( $this, 'start_ob' ) );
		add_action( 'update_option_wga-roles',  array( $this, 'update_option' ), 10, 2 );
	}

	/**
	 * This adds the options page for this plugin to the Options page
	 */
	function admin_menu() {
		add_options_page(__('Google Analytics'), __('Google Analytics'), 'manage_options', self::$page_slug, array( $this, 'settings_view' ) );
	}

	/**
	 * Register our settings
	 */
	function admin_init() {
		register_setting( 'wga', 'wga', array( $this, 'sanitize_general_options' ) );

		add_settings_section( 'wga_general', false, '__return_false', 'wga' );
		add_settings_field( 'code', __( 'Google Analytics tracking ID:', 'wp-google-analytics' ), array( $this, 'field_code' ), 'wga', 'wga_general' );
		add_settings_field( 'additional_items', __( 'Additional items to log:', 'wp-google-analytics' ), array( $this, 'field_additional_items' ), 'wga', 'wga_general' );
		add_settings_field( 'do_not_track', __( 'Visits to ignore:', 'wp-google-analytics' ), array( $this, 'field_do_not_track' ), 'wga', 'wga_general' );
	}

	/**
	 * Where the user adds their Google Analytics code
	 */
	function field_code() {
		echo '<input name="wga[code]" id="wga-code" type="text" value="' . esc_attr( $this->get_options( 'code' ) ) . '" />';
		echo '<p class="description">' . __( 'Paste your Google Analytics tracking ID (e.g. "UA-XXXXXX-X") into the field.', 'wp-google-analytics' ) . '</p>';
	}

	/**
	 * Option to log additional items
	 */
	function field_additional_items() {
		$addtl_items = array(
				'log_404s'       => __( 'Log 404 errors as /404/{url}?referrer={referrer}', 'wp-google-analytics' ),
				'log_searches'   => __( 'Log searches as /search/{search}?referrer={referrer}', 'wp-google-analytics' ),
				'log_outgoing'   => __( 'Log outgoing links as /outgoing/{url}?referrer={referrer}', 'wp-google-analytics' ),
			);
		foreach( $addtl_items as $id => $label ) {
			echo '<label for="wga_' . $id . '">';
			echo '<input id="wga_' . $id . '" type="checkbox" name="wga[' . $id . ']" value="true" ' . checked( 'true', $this->get_options( $id ), false ) . ' />';
			echo '&nbsp;&nbsp;' . $label;
			echo '</label><br />';
		}
	}

	function field_do_not_track() {
		$do_not_track = array(
				'ignore_admin_area'       => __( 'Do not log anything in the admin area', 'wp-google-analytics' ),
			);
		global $wp_roles;
		foreach( $wp_roles->roles as $role => $role_info ) {
			$do_not_track['ignore_role_' . $role] = sprintf( __( 'Do not log %s when logged in', 'wp-google-analytics' ), rtrim( $role_info['name'], 's' ) );
		}
		foreach( $do_not_track as $id => $label ) {
			echo '<label for="wga_' . $id . '">';
			echo '<input id="wga_' . $id . '" type="checkbox" name="wga[' . $id . ']" value="true" ' . checked( 'true', $this->get_options( $id ), false ) . ' />';
			echo '&nbsp;&nbsp;' . $label;
			echo '</label><br />';
		}
	}

	/**
	 * Sanitize all of the options associated with the plugin
	 */
	function sanitize_general_options( $in ) {

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

		return $out;
	}

	/**
	 * This is used to display the options page for this plugin
	 */
	function settings_view() {
		//Echo debug info if needed
		if (WGA_DEBUG) {
			echo '<pre>',var_dump($wga),'</pre>';
		}
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
	function get_url($track) {
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
	 * This injects the Google Analytics code into the footer of the page.
	 *
	 * @param bool[optional] $output - defaults to true, false returns but does NOT echo the code
	 */
	function insert_code($output=true) {
		//If $output is not a boolean false, set it to true (default)
		$output = ($output !== false);

		//get our plugin options
		$wga = wpGoogleAnalytics::get_options();
		//If the user's role has wga_no_track set to true, return without inserting code
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$role = array_shift( $current_user->roles );
			if ( 'true' == $this->get_options( 'ignore_role_' . $role ) ) {
				$ret = "<!-- Google Analytics Plugin is set to ignore your user role -->\r\n";
				if ( $output ) {
					echo $ret;
					return;
				}
				return $ret;
			}
		}
		//If the Google Analytics code has been set
		if ($wga['code'] !== false) {
			//Echo debug info if needed
			if (WGA_DEBUG) {
				echo '<pre>',var_dump($wga),'</pre>';
			}

			//If $admin is true (we're in the admin_area), and we've been told to ignore_admin_area, return without inserting code
			if (is_admin() && (!isset($wga['ignore_admin_area']) || $wga['ignore_admin_area'] != 'false')) {
				$ret = "<!-- Your Google Analytics Plugin is set to ignore Admin area -->\r\n";
				if ($output) {
					echo $ret;
				}
				return $ret;
			} elseif (is_404() && (!isset($wga['log_404s']) || $wga['log_404s'] != 'false')) {
				//Set track for 404s, if it's a 404, and we are supposed to
				$track['data'] = $_SERVER['REQUEST_URI'];
				$track['code'] = '404';
			} elseif (is_search() && (!isset($wga['log_searches']) || $wga['log_searches'] != 'false')) {
				//Set track for searches, if it's a search, and we are supposed to
				$track['data'] = $_REQUEST['s'];
				$track['code'] = "search";
			}

			//If we need to generate a special tracking URL
			if (isset($track)) {
				//get the tracking URL
				$track['url'] = wpGoogleAnalytics::get_url($track);

				//adjust the code that we output, account for both types of tracking
				$track['url'] = str_replace('&', '&amp;', $track['url']);
				$wga['code'] = str_replace("urchinTracker()","urchinTracker('{$track['url']}')", $wga['code']);
				$wga['code'] = str_replace("pageTracker._trackPageview()","pageTracker._trackPageview('{$track['url']}')", $wga['code']);

				//Echo debug info if needed
				if (WGA_DEBUG) {
					echo '<pre>',var_dump($track, $site_url),'</pre>';
				}
			}
			//output the Google Analytics code
			if ($output) {
				echo $wga['code'];
			}
			return $wga['code'];
		} else {
			//If the Google Analytics code has not been set in admin area, return without inserting code
			$ret = "<!-- You need to set up the Google Analytics Plugin -->\r\n";
			if ($output) {
				echo $ret;
			}
			return $ret;
		}
	}

	/**
	 * Used to get one or all of our plugin options
	 *
	 * @param string[optional] $option - Name of options you want.  Do not use if you want ALL options
	 * @return array of options, or option value
	 */
	function get_options($option = null) {

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
				global $wp_roles;
				// Backwards compat for when the tracking information was stored as a cap
				$maybe_role = str_replace( 'ignore_role_', '', $option );
				if ( isset( $wp_roles->roles[$maybe_role] ) ) {
					if ( isset( $wp_roles->roles[$maybe_role]['capabilities']['wga_no_track'] ) && $wp_roles->roles[$maybe_role]['capabilities']['wga_no_track'] )
						return 'true';
				}
				return false;
			}
		} else {
			return $o;
		}
	}

	/**
	 * Start our output buffering with a callback, to grab all links
	 *
	 * @todo If there is a good way to tell if this is a feed, add a seperate option for tracking outgoings on feeds
	 */
	function start_ob() {
		$log_outgoing = wpGoogleAnalytics::get_options('log_outgoing');
		// Only start the output buffering if we care, and if it's NOT an XMLRPC REQUEST & NOT a tinyMCE JS file & NOT in the admin section
		if (($log_outgoing == 'true' || $log_outgoing === false) && (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) && !is_admin() && stripos($_SERVER['REQUEST_URI'], 'wp-includes/js/tinymce') === false) {
			ob_start(array('wpGoogleAnalytics', 'get_links'));
		}
	}

	/**
	 * Grab all links on the page.  If the code hasn't been inserted, we want to
	 * insert it just before the </body> tag
	 *
	 * @param string $b - buffer contents
	 * @return string - modified buffer contents
	 */
	function get_links($b) {
		$b = preg_replace_callback("/
			<\s*a							# anchor tag
				(?:\s[^>]*)?		# other attibutes that we don't need
				\s*href\s*=\s*	# href (required)
				(?:
					\"([^\"]*)\"	# double quoted link
				|
					'([^']*)'			# single quoted link
				|
					([^'\"\s]*)		# unquoted link
				)
				(?:\s[^>]*)?		# other attibutes that we don't need
				\s*>						#end of anchor tag
			/isUx", array('wpGoogleAnalytics', 'handle_link'), $b);
		return $b;
	}

	/**
	 * If a link is outgoing, add an onclick that runs some Google JS with a
	 * generated URL
	 *
	 * @param array $m - A match from the preg_replace_callback in self::get_links
	 * @return string - modified andchor tag
	 */
	function handle_link($m) {
		$code = wpGoogleAnalytics::get_options('code');
		//get our site url...used to see if the link is outgoing.  We can't use the wordpress setting, because wordpress might not be running at the document root.
		$site_url = ( is_ssl() ? 'https://':'http://').$_SERVER['HTTP_HOST'];
		$link = array_pop($m);
		//If the link is outgoing, we modify $m[0] (the anchor tag)
		if (preg_match("/^https?:\/\//i", $link) && (strpos(strtolower($link), strtolower($site_url)) !== 0 )) {
			//get our custom link
			$track['data'] = $link;
			$track['code'] = 'outgoing';
			$track['url'] = wpGoogleAnalytics::get_url($track);

			// Check which version of the code the user is using, and user proper function
			$function = (strpos($code, 'ga.js') !== false)? 'pageTracker._trackPageview': 'urchinTracker';
			$onclick = "{$function}('{$track['url']}');";

			//If there is already an onclick, add to the beginning of it (adding to the end will not work, because too many people leave off the ; from the last statement)
			if (preg_match("/onclick\s*=\s*(['\"])/iUx",$m[0],$match)) {
				//If the onclick uses single quotes, we use double...and vice versa
				if ($match[1] == "'" ) {
					$onclick = str_replace("'", '"', $onclick);
				}
				$m[0] = str_replace($match[0], $match[0].$onclick, $m[0]);
			} else {
				$m[0] = str_replace('>', " onclick=\"{$onclick}\">", $m[0]);
			}
		}
		//return the anchor tag (modified or not)
		return $m[0];
	}

	function update_option($oldValue, $newValue) {
		/**
		 * @var WP_Roles
		 */
		global $wp_roles;

		//Add/remove wga_no_track capability for each role
		foreach ($wp_roles->roles as $role=>$role_info) {
			if (isset($newValue[$role]) && $newValue[$role] == 'true') {
				$wp_roles->add_cap($role, 'wga_no_track', true);
			} else {
				$wp_roles->add_cap($role, 'wga_no_track', false);
			}
		}
	}

	function activatePlugin() {
		// If the wga-id has not been generated, generate one and store it.
		$o = get_option('wga');
		if (!isset($o['user_agreed_to_send_system_information'])) {
			$o['user_agreed_to_send_system_information'] = 'true';
			update_option('wga', $o);
		}
	}
}

global $wp_google_analytics;
$wp_google_analytics = new wpGoogleAnalytics;

add_action( 'activate_wp-google-analytics/wp-google-analytics.php', array('wpGoogleAnalytics', 'activatePlugin'));
