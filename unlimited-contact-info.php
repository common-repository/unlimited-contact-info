<?php

/*
Plugin Name: Unlimited Contact Info
Plugin URI: http://www.geekpress.fr/wordpress/extension/unlimited-contact-info/
Description: Disable the default contact fields (AIM, Yahoo IM and Jabber) and/or add new contact fields to infinity
Version: 1.0.2
Author: GeekPress
Author URI: http://www.geekpress.fr/

	Copyright 2011 Jonathan Buttigieg
	
	This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/


// Define contants
define( 'UCI_VERSION' , '1.0' );
define( 'UCI_URL' , plugins_url(plugin_basename(dirname(__FILE__)).'/') );


class Unlimited_Contact_Info {
	
	private $fields 			= array(); // Set $fields in array
	private $default_fields 	= array(); // Set $default_fields in array
	private $settings 			= array(); // Set $setting in array
	private $checkboxes 		= array('aim', 'yim', 'jabber'); // Set $checkboxes in array
	
	
	function Unlimited_Contact_Info()
	{
		
		// Add translations
		if (function_exists('load_plugin_textdomain'))
			load_plugin_textdomain( 'unlimited-contact-info', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
		
		
		// Add menu page
		add_action('admin_menu', array(&$this, 'add_submenu'));
		
		// Settings API
		add_action('admin_init', array(&$this, 'settings_api_init'));
		
		// Check if they empty fields
		$this->check_empty_fields();
		
		// load the values recorded
		$this->fields = get_option('_unlimited_contact_info');
		
		$this->default_fields = array( 'aim' 	=> $this->fields['aim'], 
									   'yim' 	=> $this->fields['yim'], 
									   'jabber' => $this->fields['jabber']
									  );
		
		if( $this->fields ) {
			unset($this->fields['aim']);
			unset($this->fields['yim']);
			unset($this->fields['jabber']);
		}
		
		// Add filter to update contact info
		add_filter('user_contactmethods', array(&$this, 'load_contact_info'));
		
		//tell wp what to do when plugin is activated and deactivated
		if (function_exists('register_uninstall_hook'))
			register_uninstall_hook(__FILE__, array(&$this, 'deactivate'));
		
		if (function_exists('register_deactivation_hook'))
			register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
		
	}
	
	
	/**
	 * method deactivate
	 *
	 * This function is called when plugin is desactivated.
	 *
	 * @since 1.0
	**/
	function deactivate() 
	{
		delete_option('_unlimited_contact_info');	
	}
	
	
	/**
	*  method load_contact_info
	*
	* @since 1.0
	*/
	function load_contact_info( $contactmethods )
	{

		foreach( $this->default_fields as $key => $value ) {
		
			switch( $key ) {
				
				case 'aim' :
					if($value == 1)
						unset($contactmethods['aim']); // Remove the field "Aim"  
					break;
				
				case 'yim' :
					if($value == 1)
						unset($contactmethods['yim']); // Remove the field "Yahoo IM"
					break;
				
				case 'jabber' :
					if($value == 1)
						unset($contactmethods['jabber']); // Remove the field "Jabber / Google Talk"	
					break;
			}
		}
		
		if( $this->fields ) {
			foreach( $this->fields as $id )
				$contactmethods[sanitize_key( $id )] = $id;
		}
		
		return $contactmethods; 
		
	}
	
	
	/*
	 * method get_settings
	 *
	 * @since 1.0
	*/
	function get_settings()
	{
		
		// Check if $this->fields is not empty
		if( !$this->fields ) return;
		
		foreach( $this->fields as $id )
		{

			$this->settings[sanitize_key( $id )] = array(
				'section' 	=> 'general',
				'title'		=> __('Field Name', 'unlimited-contact-info'),
				'std'     	=> $id
			);
		}
	}
	
	
	/*
	 * method create_setting
	 * $args : array
	 *
	 * @since 1.0
	*/
	function create_settings( $args = array() ) {
			
		extract( $args );
		
		$field_args = array(
			'id'        => $id,
			'label_for' => $id,
			'std'		=> $std
		);
		
		
		add_settings_field( $id, $title, array( $this, 'display_settings' ), __FILE__, $section, $field_args );
	}
	
	/**
	 * method display_settings
	 *
	 * HTML output for text field
	 *
	 * @since 1.0
	 */
	public function display_settings( $args = array() ) {
		
		extract( $args );
		
		
 		echo '<input class="regular-text" type="text" id="' . $id . '" name="_unlimited_contact_info[]" value="' . esc_attr( $std ) . '" />';
 		echo '<br/><a href="#" class="help deleteRow">' . __('Remove the row', 'unlimited-contact-info') . '</a>';
 		
 		if ( $id != 'default' ) {
 			echo '<br /><span class="description">' . sprintf( __( 'Code to insert into the template file to display the value : &lt;?php the_author_meta("%s", $current_author->ID); ?&gt;', 'unlimited-contact-info'), $id) .'</span>';
 		}
		
	}

	
	/**
	 * method settings_api_init
	 *
	 * Register settings with the WP Settings API
	 *
	 * @since 1.0
	 */	
	function settings_api_init() 
	{
		
		register_setting('_unlimited_contact_info', '_unlimited_contact_info');	
		
		add_settings_section('general', __('New Contact Info', 'unlimited-contact-info'), create_function('' , 'return false;'), __FILE__);
		
		// Get the configuration of fields
		$this->get_settings();
		
		// Generate fields
		foreach ( $this->settings as $id => $setting ) {
			$setting['id'] = $id;
			$this->create_settings( $setting );
		}	
	}
	
	
	/**
	*  method check_empty_fields
	*
	* @since 1.0
	*/
	function check_empty_fields() {
		
		$options = get_option( '_unlimited_contact_info' );
		
		if( !$options ) return;
		
		$keys = array_keys( $options, '');
		
		foreach( $keys as $key )
			unset($options[$key]);
		
		// Update the new values
		update_option('_unlimited_contact_info', $options);
	}
	
	
	/**
	*  method add_submenu
	*
	* @since 1.0
	*/	
	function add_submenu() 
	{
		
		// Add submenu in menu "Settings"
		add_submenu_page( 'options-general.php', 'Unlimited Contact Info', __('Unlimited Contact Info','unlimited-contact-info'), 'administrator', __FILE__, array(&$this, 'display_page') );
	}
	
	/**
	*  method display_page
	*
	* @since 1.O
	*/
	function display_page() 
	{ 
		
		// Check if user can access to the plugin
		if (!current_user_can('administrator'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		?>
		
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e('Unlimited Contact Info Manager','unlimited-contact-info'); ?></h2>
			
			<form method="post" action="options.php">
				
				<h3><?php _e('Default Contact Info', 'unlimited-contact-info'); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e('Check to disable fields', 'unlimited-contact-info') ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Check to disable fields', 'unlimited-contact-info') ?></span></legend>
								<label for="aim">
									<input type="checkbox" <?php checked(1, (int)$this->default_fields['aim'], true); ?> value="1" id="aim" name="_unlimited_contact_info[aim]"> <?php _e('Disable AIM field', 'unlimited-contact-info') ?>
								</label>
								</br>
								
								<label for="yim">
									<input type="checkbox" <?php checked(1, (int)$this->default_fields['yim'], true); ?> value="1" id="yim" name="_unlimited_contact_info[yim]"> <?php _e('Disable Yahoo IM field', 'unlimited-contact-info') ?>
								</label>
								</br>
								
								<label for="jabber">
									<input type="checkbox" <?php checked(1, (int)$this->default_fields['jabber'], true); ?> value="1" id="jabber" name="_unlimited_contact_info[jabber]"> <?php _e('Disable Jabber/Google Talks field', 'unlimited-contact-info') ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				
			    <?php 
			    	
			    	settings_fields('_unlimited_contact_info');
			    	
			    	if( !$this->fields ) {?>
			    	
			    	<h3><?php _e('New Contact Info', 'unlimited-contact-info'); ?></h3>
			    	<table class="form-table">
			    	</table>
			    	
			    	<?php
			    	}
			    	else {
  						do_settings_sections(__FILE__);
			    	}
			    	
  				?>
  				
  				<p>
  					<button id="addRow"  class="button button-secondary button-highlighted"><?php _e('Add a new field', 'unlimited-contact-info'); ?></button>
  				</p>
  				
  				<?php
					submit_button( __('Save Changes') );
				?>
			    
			</form>
		</div>
		
		<script type="text/javascript">
			jQuery(function(){
	
				/* Add field */
				jQuery('#addRow').click(function() {
					
					/* Clone last input */
					jQuery('.form-table:last').append( '<tr valign="top"><th scope="row"><label for="default"><?php _e('Field Name', 'unlimited-contact-info'); ?></label></th><td><input class="regular-text" id="default" name="_unlimited_contact_info[]" value="" type="text"><br><a href="#" class="help deleteRow"><?php _e('Remove the row', 'unlimited-contact-info'); ?></a></td></tr>' );
					
					return false;
				});
				
				/* Delete Field */
			 	 jQuery('.deleteRow').live('click', function() {
			 		jQuery(this).parents('tr').remove();
			 		return false;
			 	 });
			});
		</script>
		
	<?php
	}
	
}

// Start this plugin once all other plugins are fully loaded
global $Unlimited_Contact_Info; $Unlimited_Contact_Info = new Unlimited_Contact_Info();
?>