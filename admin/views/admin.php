<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Maps
 * @author    Sean Corgan <seancorgan@gmail.com>
 * @copyright 2015
 */
?>

<div class="wrap">
	<h2>Maps Options</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'maps-settings-group' ); ?>
	    <?php do_settings_sections( 'maps-settings-group' ); ?>
	    <table class="form-table">

	    <!--
	        <tr valign="top">
		        <th scope="row">Google Client ID</th>
		        <td><input type="text" name="client_id" value="<?php echo get_option('client_id'); ?>" /></td>
	        </tr>

	        <tr valign="top">
		        <th scope="row">Google Client Secret</th>
		        <td><input type="text" name="client_secret" value="<?php echo get_option('client_secret'); ?>" /></td>
	        </tr>

	    -->

	        <tr valign="top">
		        <th scope="row">API Key</th>
		        <td><input type="text" name="api_key" value="<?php echo get_option('api_key'); ?>" /></td>
	        </tr>

	        <tr valign="top">
		        <th scope="row">Map Icon</th>
		        <td>
		        <?php $map_icon = get_option('map_icon');


		        	if(empty($map_icon)): ?>
						<a id="map_icon" href="#"><img src="<?php echo plugin_dir_path(dirname(__FILE__)).'assets/img/google-marker.png'; ?>" /></a>
		        	<?php
		        	else:
		        	?>
		        	 	<a id="map_icon" href="#"><img src="<?php echo get_option('map_icon'); ?>" /></a>
		        	<?php
		        	endif;
		        ?>
		        	<input type="hidden" name="map_icon" id="icon_url" value="<?php echo get_option('map_icon'); ?>" />
		        </td>

	        </tr>


	    </table>
    	<?php submit_button(); ?>
    </form>
</div>
