<?php
/* 
Plugin Name: autocomplete
Plugin URI: http://github.com/amirmasoud/autocomplete/
Description: US city autocompleter
Version: 1.0
Author: AmirMasoud Sheidayi
Author URI: http://chakosh.ir/
License: GPLv2 or later
*/

/**
 * add autocomplete to menu
 */
add_action( 'admin_menu', 'autocomplete_menu' );
function autocomplete_menu()
{
	add_menu_page( __('Autocomplete', 'ac'), __('Autocomplete', 'ac'), 'manage_options', 'autocomplete', 'autocomplete_admin', 'dashicons-location', 81 );
}

/**
 * admin page for enduser
 * @return string
 */
function autocomplete_admin()
{
	echo '<p>' . __('use', 'ac') . ' <code>[city-autocomplete]</code> ' .  __('on page/post or in the widget area.', 'ac') . '</p>';
}

/**
 * output input area and js/css required for autocomplete
 * @return html
 */
function autocomplete_shortcode() {
	wp_enqueue_style('ac-font-awesome', plugins_url( '/css/font-awesome.min.css', __FILE__ ));
	wp_enqueue_style('ac-smoothness', 'http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css');
	wp_enqueue_style('ac-style', plugins_url('style.css', __FILE__ ));
    wp_enqueue_script('ac-script', plugins_url('script.js', __FILE__ ), array('jquery', 'jquery-ui-autocomplete'));
	wp_localize_script( 'ac-script', 'ac', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));

    $output = 
    '<div class="ui-widget">
		<div class="search-area">
			<input class="search" type="text" autofocus="autofocus">
			<div class="searching">
				<i class="fa fa-circle-o-notch fa-spin"></i>
			</div><!-- .searching -->
		</div><!-- .search-area -->
	</div><!-- .ui-widget -->';

	echo $output;
}

/**
 * add shortcode
 * @return void
 */
add_action( 'init', 'autocomplete_register_shortcode' );
function autocomplete_register_shortcode() {
    add_shortcode( 'city-autocomplete', 'autocomplete_shortcode' );
}

/**
 * proccess ajax request
 * @return json
 */
add_action( 'wp_ajax_location_search', 'autocomplete_location_search' );
function autocomplete_location_search() {
	if (isset($_GET['term'])) :
		global $wpdb;

		$table_name = $wpdb->prefix . "population";

		$locations = $wpdb->get_results( $wpdb->prepare( 
			"
			SELECT      location, slug, population 
			FROM        $table_name 
			WHERE       location LIKE %s 
			ORDER BY population 
			DESC LIMIT 10
			",
			'%' . $_GET['term'] . '%'
		) );

		/**
		 * get result ready
		 * @var array
		 */
		$result = array();
		foreach ( $locations as $loacation ) 
		{ 
	        $result[] = array(
	            'label' => $loacation->location, 
	            'slug' => get_site_url() . '/location-search/' . $loacation->slug
	        );
		}

	    if (empty($result))
	        $result[] = array(
	            'label' => 'No Result', 
	            'slug' => '#'
	        );

	    echo json_encode($result);
	endif;

	// end of ajax call
	die();
}

/**
 * install database schema if not exists
 * @return void
 */
register_activation_hook( __FILE__, 'autocomplete_install' );
function autocomplete_install() {
	global $wpdb;

	$table_name = $wpdb->prefix . "population";

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
			 `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			 `location` varchar(150) NOT NULL,
			 `slug` varchar(150) NOT NULL,
			 `population` int(10) unsigned NOT NULL
			)ENGINE=InnoDB $charset_collate";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$wpdb->query("
				ALTER TABLE $table_name
				ADD INDEX USING HASH (location)
				");

    // Clear the permalinks after the post type has been registered
    flush_rewrite_rules(); 
}

/**
 * insert table info, possible to generate error because of max_execuation time
 * @return void
 */
register_activation_hook( __FILE__, 'autocomplete_install_data' );
function autocomplete_install_data()
{
	global $wpdb;

	$table_name = $wpdb->prefix . "population";

	$handle = fopen(plugins_url( 'data.csv', __FILE__ ), 'r');
	while ($data = fgetcsv($handle,1000,"	")) :
	    if ($data[0]) :
	    	$location = $data[1];
	    	$slug = $data[2];
	    	$population = $data[3];
			
			$wpdb->insert( 
				$table_name, 
				array( 
					'location' 		=> $location,
					'slug' 			=> $slug, 
					'population' 	=> $population
				) 
			);
	    endif;
	endwhile;
}

/**
 * drop population table
 * @return void
 */
register_deactivation_hook( __FILE__, 'autocomplete_deactivation' );
function autocomplete_deactivation() {
	global $wpdb;

	$table_name = $wpdb->prefix . "population";

	$wpdb->query( 
		$wpdb->prepare("DROP TABLE $table_name")
	);

    // Clear the permalinks to remove our post type's rules
    flush_rewrite_rules();
}