<?php 
/**
 * maps
 *
 * @package   MapsAdmin
 * @author    Sean Corgan <seancorgan@gmail.com>
 * @copyright 2014 
 */

 /**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-maps.php`
 *
 * @package MapsAdmin
 * @author  Sean Corgan <seancorgan@gmail.com>
 */
class MapsAdmin {
	
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// settings
		
		try {  
			$this->set_api_key(get_option('api_key'));
		//	$this->set_client_id(get_option('client_id')); 
		//	$this->set_client_secret(get_option('client_secret'));
		} catch (Exception $e) { 
			$this->error_message = $e->getMessage(); 
		 	add_action( 'admin_notices', array($this, 'settings_missing_message'));
		}

		$plugin = Maps::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
		
		add_action( 'init', array($this, 'maps_register_locations' ));

		add_action( 'save_post', array($this, 'save_map_meta' ));

		//call register settings function
		add_action( 'admin_init', array($this, 'register_mysettings' ));

		add_action( 'init', array($this, 'maps_acf_register_fields' ));

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
	*  Displays Error Messages
	*/	
	function settings_missing_message() {
	    ?>
	    <div class="error">
	        <p><?php _e($this->error_message); ?></p>
	    </div>
	    <?php
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Maps::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Maps::VERSION );
		}

		wp_enqueue_media();

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		*/
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Maps'),
			__( 'Maps'),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

	function set_api_key($api_key) { 
		if(empty($api_key)): 
			throw new Exception("API Key Missing");
		else: 
			$this->api_key = $api_key; 
		endif; 
	}
/*
	function set_client_id($set_client_id) { 
		if(empty($set_client_id)): 
			throw new Exception("Client Secret is missing");
		else: 
			$this->set_client_id = $set_client_id; 
		endif; 
	}

	function set_client_secret($client_secret) { 
		if(empty($client_secret)): 
			throw new Exception("API Key Missing");
		else: 
			$this->client_secret = $client_secret; 
		endif; 
	}
*/ 

	/**
	* Registers Location Post Type
	* @uses $wp_post_types Inserts new post type object into the list
	*
	* @return object|WP_Error the registered post type object, or an error object
	*/
	function maps_register_locations() {
	
		$labels = array(
			'name'                => __( 'Locations', 'location' ),
			'singular_name'       => __( 'Location', 'location' ),
			'add_new'             => _x( 'Add New Location', 'location', 'location' ),
			'add_new_item'        => __( 'Add New Location', 'location' ),
			'edit_item'           => __( 'Edit Location', 'location' ),
			'new_item'            => __( 'New Location', 'location' ),
			'view_item'           => __( 'View Location', 'location' ),
			'search_items'        => __( 'Search Locations', 'location' ),
			'not_found'           => __( 'No Locations found', 'location' ),
			'not_found_in_trash'  => __( 'No Locations found in Trash', 'location' ),
			'parent_item_colon'   => __( 'Parent Location:', 'location' ),
			'menu_name'           => __( 'Locations', 'location' ),
		);
	
		$args = array(
			'labels'                   => $labels,
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => null,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title', 'editor', 'thumbnail'
				)
		);
	
		register_post_type( 'location', $args );
	}

	/**
	 * The action when a location is updated should fire off a gelocation script to update lat and lng fields 
	 *
	 * @param int $post_id The ID of the post.
	 */
	function save_map_meta( $post_id ) {

		if(empty($_POST['post_type'])) { 
			return; 
		}
	 
	    // If this isn't a locations post, don't update it.
	    if ( 'location' != $_POST['post_type'] ) {
	        return;
	    }


	    if(!empty($_REQUEST['fields']['field_5307e6fb795e4'])):

	    	$address = $_REQUEST['fields']['field_5307e6fb795e4'];
	    	
	    	$geo = $this->geolocate($address);
	    	
	    	if($geo) { 
	    		update_post_meta($post_id, 'lat', $geo['lat']);
	    		update_post_meta($post_id, 'lng', $geo['lng']);
	    	}
   	
	  	endif;

	}


	function send_request($url, $data) {
		// Get cURL resource
		//url-ify the data for the get  : Actually create datastring
	    $fields_string = '';

	    foreach($data as $key => $value) {
	    	$fields_string[]=$key.'='.urlencode($value).'&'; 
	    	$urlStringData = $url.'?'.implode($fields_string);
		} 

	    $ch = curl_init();

	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10); # timeout after 10 seconds, you can increase it
	    curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
	    curl_setopt($ch, CURLOPT_URL, $urlStringData ); #set the url and get string together

	    $return = curl_exec($ch);
	    curl_close($ch);

	    return $return;
	}

	function geolocate($address) { 
		
		$response = $this->send_request('https://maps.googleapis.com/maps/api/geocode/json', array('address' => $address, 'sensor' => 'true'));

		$r = json_decode($response);
		
		if(empty($r)) { 
			throw new Exception("No response from Google Gelocation", 1); 
		}

		if(empty($r->results[0]->geometry->location->lat)): 
			throw new Exception("Google did not return a Location Lat Lng", 1);
		endif; 

		$lat = $r->results[0]->geometry->location->lat; 
		$lng = $r->results[0]->geometry->location->lng; 

		if(!empty($lat) && !empty($lng)): 
			return array('lat' => $lat, 'lng' => $lng);
		else: 
			return false; 
		endif;   

	}

	/**
	* Register Plugin settings
	*/
	function register_mysettings() {
		//register our settings
		register_setting( 'maps-settings-group', 'api_key' );
		register_setting( 'maps-settings-group', 'client_id' );
		register_setting( 'maps-settings-group', 'client_secret' );
		register_setting( 'maps-settings-group', 'map_icon' );
	}

	function maps_acf_register_fields() { 
		if(function_exists("register_field_group"))
		{
			register_field_group(array (
				'id' => 'acf_locations',
				'title' => 'Locations',
				'fields' => array (
					array (
						'key' => 'field_530e813ef40c7',
						'label' => 'Email Address',
						'name' => 'email',
						'type' => 'text',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'none',
						'maxlength' => '',
					),
					array (
						'key' => 'field_530e814cf40c8',
						'label' => 'Phone Number',
						'name' => 'phone',
						'type' => 'text',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'none',
						'maxlength' => '',
					),
					array (
						'key' => 'field_5307e6fb795e4',
						'label' => 'Address',
						'name' => 'address',
						'type' => 'text',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'html',
						'maxlength' => '',
					),
					array (
						'key' => 'field_530bb60b1781e',
						'label' => 'Latitutde',
						'name' => 'lat',
						'type' => 'number',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
					),
					array (
						'key' => 'field_530bb6281781f',
						'label' => 'Longitude',
						'name' => 'lng',
						'type' => 'number',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
					),

					array (
						'key' => 'field_530e3da36330f',
						'label' => 'Info Popup',
						'name' => 'info_popup',
						'type' => 'wysiwyg',
						'instructions' => 'The popup that appears above the marker.',
						'default_value' => '',
						'toolbar' => 'full',
						'media_upload' => 'yes',
					),
				),
				'location' => array (
					array (
						array (
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'location',
							'order_no' => 0,
							'group_no' => 0,
						),
					),
				),
				'options' => array (
					'position' => 'normal',
					'layout' => 'default',
					'hide_on_screen' => array (
						0 => 'the_content',
					),
				),
				'menu_order' => 0,
			));
		}

	}

}