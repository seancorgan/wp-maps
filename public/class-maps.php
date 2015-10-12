<?php
//* External Class to extend WP_QUERY to do haversines to calc distance of locations.
// http://fyaconiello.github.io/wp-geo-posts/
require('wp_geo.php');
/**
 * maps
 *
 * @package   Maps
 * @author    Sean Corgan <seancorgan@gmail.com>
 * @copyright 2015
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-maps-admin.php`
 *
 * @package Maps
 * @author  Sean Corgan <seancorgan@gmail.com>
 */
class Maps {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'maps';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'map', array($this, 'map_shortcode') );
		add_shortcode( 'map_search', array($this, 'map_search') );
		add_shortcode( 'map_list', array($this, 'map_list_shortcode') );


		if( is_admin() ) {
			add_action( 'wp_ajax_get_locations', array( $this, 'map_get_locations' ));
			add_action( 'wp_ajax_nopriv_get_locations', array( $this, 'map_get_locations' ));
		}

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('underscore');
		wp_enqueue_script('backbone');
	}


	/**
	 * Adds the map shortcode to render the map
	 * @todo Other shortcodes might rely on the equeing of scripts, if we decide to change this, we must call these some other way.
	 */
	public function map_shortcode( $atts, $content = null ) {

		wp_enqueue_script( $this->plugin_slug . '-docCookie', plugins_url( 'assets/js/docCookie.js', __FILE__ ), array(), self::VERSION );
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery', 'underscore', 'backbone' ), self::VERSION );

		if(!empty($_GET['apply'])):
			wp_enqueue_style( $this->plugin_slug . '-special-styles', plugins_url( 'assets/css/special-styles.css', __FILE__));
		endif;

		wp_localize_script( $this->plugin_slug . '-plugin-script', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'api_key' => get_option('api_key') ) );

	    $html = '<div width="100%" id="map-canvas"/></div>';
		return $html;
	}

	/**
	 * Short Code to add map to page
	 * @param  array $atts    - array of attributes that are passed in from the shortcode
	 * @param  string $content - content in the shortcode
	 * @return string          html output of shortcode
	 * @since 1.0
	 */
	public function map_list_shortcode( $atts, $content = null ) {

	    $terms = get_terms('city');
	    if(!empty($terms)):
	    $html = '<ul class="terms">';
	    	foreach ($terms as $term) {
	    	  	$html .= '<li data-id="'.$term->term_id.'">'.$term->name.'</li>';
	    	 }
	    endif;
	    $html .= '</ul>';
	    $html .= '<ul id="map-items"></ul>';
		return $html;
	}

	/**
	 * Create shortcode for the map search form
	 * @param  array $atts    - array of attributes that are passed in from the shortcode
	 * @param  string $content - content in the shortcode
	 * @return string          html output of shortcode
	 * @since 1.0
	 */
	public function map_search($atts, $content = null) {

		$html = '<form id="map_search" action="#" class="clearfix">
			<input type="search" placeholder="Enter City & State or Zip Code" name="mapsearch" />
			<input type="submit" value="submit" />
		</form>';
		return $html;
	}
	/**
	 * Gets list of locations, and returns Bult HTML
	 * @todo Why not use a client side template engine for this stuff? Would be more effecient.
	 * @since 1.0
	 */
	public function map_get_locations() {
		$args = array('post_type' => 'location',
  		  'posts_per_page' => -1);
		// The Query

		if(isset($_REQUEST['lat']) && isset($_REQUEST['lng'])) {
			$args['latitude'] = $_REQUEST['lat'];
			$args['longitude'] = $_REQUEST['lng'];
		}

		$query = new WP_GeoQuery( $args );
		$locations = array();

		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				$title = get_the_title();
				$id = get_the_ID();
				$location = array('id' => $id, 'title' => $title);
				global $post;

				$map_icon = get_option('map_icon');
				if(isset($map_icon)):
					$location['map_icon'] = get_option('map_icon');
				endif;

				$fields = get_fields(get_the_ID());
				$info_title = '<span class="popup-title">'.$title.'</span>';
				$info_title .= $fields['info_popup'];
				$apply_link = get_permalink(177);

				$apply_button = '<a class="apply-now-btn" href="'.$apply_link.'?location_id='.$id.'&career_link=513">Apply Now</a>';

				$address = urlencode($fields['address']);

				$direction_link = '<a class="get-directions-btn" target="_blank" href="https://www.google.com/maps/preview?daddr='.$address.'"">Get Directions</a>';

				$info_title .= $direction_link;
				$info_title .= $apply_button;

				$thumb = get_the_post_thumbnail( $id, 'thumb');

				$info_pop = '<div class="left">'.$info_title.'</div>';
				$info_pop .= '<div class="left">'.$thumb.'</div>';

				$categories = get_the_terms($id, 'city');

				$l = end($categories);

				$fields['distance'] = number_format((float)$post->distance, 2, '.', '');
				$fields['info_popup'] = $info_pop;

				if(!empty($categories)):
					$fields['category'] = $l->term_id;
					$fields['category_name'] = $l->name;
				endif;

				$locations[] = array_merge($location, $fields);
			endwhile;
		endif;

		echo json_encode($locations);
		die();
	}
}
