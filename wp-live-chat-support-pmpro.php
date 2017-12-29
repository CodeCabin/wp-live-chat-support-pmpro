<?php
/*
  Plugin Name: WP Live Chat Support - PMPro Integration
  Plugin URI: https://wp-livechat.com
  Description: Integrate PMPro with WP Live Chat Support
  Version: 0.0.1
  Author: WPLiveChat
  Author URI: http://codecabin.co.za

 * 1.0.00 - 2017-12-29
 * - Basic filter, allows only members to use the chat
 * - Advanced filter, allow only members with the correct access-level for a page to use the chat
 */


class WP_Live_Chat_Support_Ext_PMPro { 

	var $current_page_id = "";
	var $my_pages = "";
	var $current_user_membership_level = "";
	
	var $chat_not_allowed_explanation = "You need to be a member to use the chat, click on the button below and create a membership account";
	var $sign_up_redirect_url = "http://localhost:8888/wordpress/wp-login.php";
	var $sign_up_btn_label = "Sign Up";

	var $is_chat_for_members_only = false;
	var $is_filter_chat_by_perm_level_enabled = false;
	var $is_chat_allowed = true;

	var $restricted_access_level_msg = "";
	var $upgrade_user_level_btn_label = "Upgrade plan";

	/**
	 * Build the plugin extension by adding hooks and filters to the admin and regular user pages
	 */
	public function __construct() {
		// Admin
		add_filter( "wplc_filter_setting_tabs", array($this, "wplc_pmpro_tab"));
		
		add_action( "wplc_hook_settings_page_more_tabs", array($this, "wplc_pmpro_tab_content"));
		add_action( "wplc_hook_head", array($this, "wplc_pmpro_settings_save"));
		add_action( "admin_enqueue_scripts", array($this, "wplc_pmpro_add_admin_style_and_scripts"));
		
		// User
		add_action( 'wp_head', array($this, 'wplc_pmpro_script'), 1, 1);
		add_action( 'wp_enqueue_scripts', array($this, 'wplc_pmpro_styles'), 1, 2);

		add_filter( 'wplc_filter_live_chat_box_html_1st_layer', array($this, 'wplc_pmpro_clean_chat_box_text'), 1, 2);
		add_filter( 'wp_footer', array($this, 'wplc_pmpro_get_page_info'), 1, 3);
	}

	// ..................................//
	// Functions related to the Admin UI //
	// ..................................//
	/**
	 * Add the PMPro tab on the settings area of the WPLC plugin
	 */
	function wplc_pmpro_tab( $array ) {
		$array['pmpro'] = array(
			'href' 	=> '#wplc-pmpro',
			'icon' 	=> 'fa fa-user-plus',
			'label' => __("PMPro", "wplivechat")
		);
		
		return $array;
	}

	/**
	 * Build the UI of the WPLC-PMPro admin section
	 */
	function wplc_pmpro_tab_content() {
		$wplc_pmpro_settings = get_option( "wplc_pmpro_settings" );

		// Open the content of the PMPro section of the settings menu option
		$content = "<div id='wplc-pmpro'>";
		$content .= "<h2>".__("PMPro Settings", "wplivechat")."</h2>";
		$content .= "<p><img src='".plugins_url('/images/pmpro-logo.png', __FILE__)."' class='pmpro-logo' alt='' title='' /></p>";
		$content .= "<table class='form-table wp-list-table widefat fixed striped pages' >";

		// Default membership filter settings
		// Checkbox, enable WPLC integration with PMPro
		$content .= "<tr>";
		$content .= "<td width='300'>".__("Enable chat only for members", "wplivechat")."</td>";
		if ( isset( $wplc_pmpro_settings['wplc_pmpro_enabled'] ) && $wplc_pmpro_settings['wplc_pmpro_enabled'] === true ) { 
			$checked = 'checked'; 
		} else { 
			$checked = '';
		}
		$content .= "<td><input id='wplc_pmpro_enable' type='checkbox' name='wplc_pmpro_enable' value='1' $checked /></td>";
		$content .= "</tr>";

		// Text, displayed to non-members to justify why only members can use the chat
		$explanation = 'Explanation of why a User must be a member to access the chat';

		if ( isset( $wplc_pmpro_settings['wplc_pmpro_chat_not_allowed_explanation'] ) ) { 
			$explanation = $wplc_pmpro_settings['wplc_pmpro_chat_not_allowed_explanation'];
		}

		$content .= "<tr>";
		$content .= "<td width='300'>".__("Notice message which will be shown to all non-members of the site inside the chat box", "wplivechat")."</td>";
		$content .= "<td>";
		$content .= "<input id='wplc_pmpro_chat_not_allowed_explanation' type='text' name='wplc_pmpro_chat_not_allowed_explanation' value='".$explanation."' placeholder='Only members can use the chat'/>";
		$content .= "</td>";
		$content .= "</tr>";

		// Text, URL of the redirect button at the chat not allowed msg
		$sign_up_redirect_url = '';

		if ( isset( $wplc_pmpro_settings["wplc_pmpro_sign_up_redirect_url"] ) ) { 
			$sign_up_redirect_url = $wplc_pmpro_settings["wplc_pmpro_sign_up_redirect_url"];
		}

		$content .= "<tr>";
		$content .= "<td width='300'>".__("URL of the page to where the User is redirected by clicking on the button inside the chat box", "wplivechat")."</td>";
		$content .= "<td>";
		$content .= "<input id='wplc_pmpro_sign_up_redirect_url' type='text' name='wplc_pmpro_sign_up_redirect_url' value='".$sign_up_redirect_url."' placeholder='Example:http://localhost:8888/wordpress/login/.'/>";
		$content .= "</td>";
		$content .= "</tr>";

		// Text, Label of the redirect button
		$sign_up_btn_label = '';

		if ( isset( $wplc_pmpro_settings["wplc_pmpro_sign_up_btn_label"] ) ) { 
			$sign_up_btn_label = $wplc_pmpro_settings["wplc_pmpro_sign_up_btn_label"];
		}

		$content .= "<tr>";
		$content .= "<td width='300'>".__("Label of the redirect button which is displayed when non-members try to use the chat", "wplivechat")."</td>";
		$content .= "<td>";
		$content .= "<input id='wplc_pmpro_sign_up_btn_label' type='text' name='wplc_pmpro_sign_up_btn_label' value='".$sign_up_btn_label."' placeholder='Example:Click here to sign up.'/>";
		$content .= "</td>";
		$content .= "</tr>";

		// Settings of the restricted access level membership filter
		// Checkbox, enable restriction for level of access
		$content .= "<tr>";
		$content .= "<td width='300'>".__("Enable chat only if the user has the correct access-level to access the content of a page", "wplivechat")."</td>";
		if ( isset( $wplc_pmpro_settings['wplc_pmpro_level_access_restriction_enabled'] ) && $wplc_pmpro_settings['wplc_pmpro_level_access_restriction_enabled'] === true ) { 
			$checked = 'checked'; 
		} else { 
			$checked = ''; 
		}
		$content .= "<td><input id='wplc_pmpro_level_access_restriction_enable' type='checkbox' name='wplc_pmpro_level_access_restriction_enable' value='1' $checked /></td>";
		$content .= "</tr>";

		// Text, Msg restricted access level
		$restricted_access_level_msg = '';

		if ( isset( $wplc_pmpro_settings["wplc_pmpro_restricted_access_level_msg"] ) ) { 
			$restricted_access_level_msg = $wplc_pmpro_settings["wplc_pmpro_restricted_access_level_msg"];
		}

		$content .= "<tr>";
		$content .= "<td width='300'>".__("Message that is displayed to a User if he/she doesn't have the correct access-level for a page", "wplivechat")."</td>";
		$content .= "<td>";
		$content .= "<input id='wplc_pmpro_restricted_access_level_msg' type='text' name='wplc_pmpro_restricted_access_level_msg' value='".$restricted_access_level_msg."' placeholder='i.e: Your current access level is !!!access-level!!!, it doesn't allow you to use the chat on this page'/>";
		$content .= "</td>";
		$content .= "</tr>";

		// Text, Label upgrade
		$upgrade_btn_label = '';

		if ( isset( $wplc_pmpro_settings["wplc_pmpro_label_upgrade_user_level_btn"] ) ) { 
			$upgrade_btn_label = $wplc_pmpro_settings["wplc_pmpro_label_upgrade_user_level_btn"];
		}

		$content .= "<tr>";
		$content .= "<td width='300'>".__("Label of the upgrade plan button", "wplivechat")."</td>";
		$content .= "<td>";
		$content .= "<input id='wplc_pmpro_label_upgrade_user_level_btn' type='text' name='wplc_pmpro_label_upgrade_user_level_btn' value='".$upgrade_btn_label."' placeholder='i.e: Click here to upgrade'/>";
		$content .= "</td>";
		$content .= "</tr>";

		// Close the content of the PMPro section of the settings menu option
		$content .= "</table>";
		$content .= "</div>";

		echo $content;
	}

	/**
	 * Handle the upsert operation of the WPLC-PMPro settings
	 */
	function wplc_pmpro_settings_save() {
		if ( isset( $_POST['wplc_save_settings'] ) ){
			
			// Default membership filter settings
			if ( isset( $_POST['wplc_pmpro_enable'] ) && $_POST['wplc_pmpro_enable'] == '1' ) {
				$wplc_pmpro_enabled = true; 
			} else { 
				$wplc_pmpro_enabled = false; 
			}

			if ( isset( $_POST['wplc_pmpro_chat_not_allowed_explanation'] ) ) {
				$wplc_pmpro_chat_not_allowed_explanation = sanitize_text_field( $_POST['wplc_pmpro_chat_not_allowed_explanation'] ); 
			} else { 
				$wplc_pmpro_chat_not_allowed_explanation = false; 
			}

			if ( isset( $_POST['wplc_pmpro_sign_up_redirect_url'] ) ) {
				$wplc_pmpro_sign_up_redirect_url = sanitize_text_field( $_POST['wplc_pmpro_sign_up_redirect_url'] ); 
			} else { 
				$wplc_pmpro_sign_up_redirect_url = false; 
			}

			if ( isset( $_POST['wplc_pmpro_sign_up_btn_label'] ) ) {
				$wplc_pmpro_sign_up_btn_label = sanitize_text_field( $_POST['wplc_pmpro_sign_up_btn_label'] ); 
			} else { 
				$wplc_pmpro_sign_up_btn_label = false; 
			}

			// Restricted access level membership filter settings
			if ( isset( $_POST['wplc_pmpro_level_access_restriction_enable'] ) && $_POST['wplc_pmpro_level_access_restriction_enable'] == '1' ) {
				$wplc_pmpro_level_access_restriction_enabled = true; 
			} else { 
				$wplc_pmpro_level_access_restriction_enabled = false; 
			}
			
			if ( isset( $_POST['wplc_pmpro_restricted_access_level_msg'] ) ) {
				$wplc_pmpro_restricted_access_level_msg = sanitize_text_field( $_POST['wplc_pmpro_restricted_access_level_msg'] ); 
			} else { 
				$wplc_pmpro_restricted_access_level_msg = false; 
			}

			if ( isset( $_POST['wplc_pmpro_label_upgrade_user_level_btn'] ) ) {
				$wplc_pmpro_label_upgrade_user_level_btn = sanitize_text_field( $_POST['wplc_pmpro_label_upgrade_user_level_btn'] ); 
			} else { 
				$wplc_pmpro_label_upgrade_user_level_btn = false; 
			}

			$wplc_pmpro_settings = array(
				'wplc_pmpro_enabled' 	                      => $wplc_pmpro_enabled,
				'wplc_pmpro_level_access_restriction_enabled' => $wplc_pmpro_level_access_restriction_enabled,
				'wplc_pmpro_chat_not_allowed_explanation'     => $wplc_pmpro_chat_not_allowed_explanation,
				'wplc_pmpro_sign_up_redirect_url' 	          => $wplc_pmpro_sign_up_redirect_url,
				'wplc_pmpro_sign_up_btn_label' 	              => $wplc_pmpro_sign_up_btn_label,
				'wplc_pmpro_restricted_access_level_msg'      => $wplc_pmpro_restricted_access_level_msg,
				'wplc_pmpro_label_upgrade_user_level_btn'     => $wplc_pmpro_label_upgrade_user_level_btn
			);

			update_option( "wplc_pmpro_settings", $wplc_pmpro_settings );
		}
	}

	/**
	 * Enqueue the user styles and scripts
	 */
	function wplc_pmpro_add_admin_style_and_scripts() {
		wp_enqueue_style( 'wplc-admin-pmpro', plugins_url( '/css/wplc-admin-pmpro.css', __FILE__ ) );

		wp_register_script('wplc-admin-pmpro-js', plugins_url(plugin_basename(dirname(__FILE__)))."/js/wplc-admin-pmpro.js", array('jquery'));
		wp_enqueue_script('wplc-admin-pmpro-js');
	}

	// .................................//
	// Functions related to the User UI //
	// .................................//
	/**
	 * Enqueue the user scripts
	 */
	function wplc_pmpro_script() {
		wp_register_script('wplc-pmpro-js', plugins_url(plugin_basename(dirname(__FILE__)))."/js/wplc-pmpro.js", array('jquery'));
		wp_enqueue_script('wplc-pmpro-js');
	}

	/**
	 * Enqueue the user styles
	 */
	function wplc_pmpro_styles() {
		wp_enqueue_style( 'wplc-pmpro-css', plugins_url( '/css/wplc-pmpro.css', __FILE__ ) );
	}

	/**
	 * Intercept the method that injects an important part of the WPLC chat button, 
	 * gather the admin settings and build a set of new settings to determine how the WPLC button 
	 * is going to be displayed.
	 * 
	 * @param string $button_content the incoming HTML code from the WPLC plugin.
	 * @return string incoming HTML code from the WPLC plugin + new settings for the wplc-pmpro-js script
	 */
	function wplc_pmpro_clean_chat_box_text($button_content) {
		$resp = "";

		$wplc_pmpro_settings = get_option( "wplc_pmpro_settings" );
		
		// Default membership filter settings
		if ( isset( $wplc_pmpro_settings['wplc_pmpro_enabled'] ) && $wplc_pmpro_settings['wplc_pmpro_enabled'] == '1' ) {
			$this->is_chat_for_members_only = $wplc_pmpro_settings['wplc_pmpro_enabled']; 
		}

		if ( isset( $wplc_pmpro_settings['wplc_pmpro_chat_not_allowed_explanation'] ) ) {
			$this->chat_not_allowed_explanation = sanitize_text_field( $wplc_pmpro_settings['wplc_pmpro_chat_not_allowed_explanation'] ); 
		}

		if ( isset( $wplc_pmpro_settings['wplc_pmpro_sign_up_redirect_url'] ) ) {
			$this->sign_up_redirect_url = sanitize_text_field( $wplc_pmpro_settings['wplc_pmpro_sign_up_redirect_url'] ); 
		}

		if ( isset( $wplc_pmpro_settings['wplc_pmpro_sign_up_btn_label'] ) ) {
			$this->sign_up_btn_label = sanitize_text_field( $wplc_pmpro_settings['wplc_pmpro_sign_up_btn_label'] ); 
		}

		// Restricted access level membership filter settings
		if ( isset( $wplc_pmpro_settings['wplc_pmpro_level_access_restriction_enabled'] ) && $wplc_pmpro_settings['wplc_pmpro_level_access_restriction_enabled'] == '1' ) {
			$this->is_filter_chat_by_perm_level_enabled = $wplc_pmpro_settings['wplc_pmpro_level_access_restriction_enabled']; 
		}
		
		if ( isset( $wplc_pmpro_settings['wplc_pmpro_restricted_access_level_msg'] ) ) {
			$this->restricted_access_level_msg = sanitize_text_field( $wplc_pmpro_settings['wplc_pmpro_restricted_access_level_msg'] ); 
		}

		if ( isset( $wplc_pmpro_settings['wplc_pmpro_label_upgrade_user_level_btn'] ) ) {
			$this->upgrade_user_level_btn_label = sanitize_text_field( $wplc_pmpro_settings['wplc_pmpro_label_upgrade_user_level_btn'] ); 
		}
		
		// Only apply the filters to the chat button if the user has selected this option
		if ($this->is_chat_for_members_only) {
			// So far the User doesn't have a membership account, block the use of the chat
			$this->is_chat_allowed = false;

			// Get the current User
			$current_user = wp_get_current_user();
			
			if ($current_user != null) {

				if ($current_user->ID != null) {
					
					// Identify if the current user is logged in and specially, if the User has a membership level
					if (is_user_logged_in() && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel()) {
						$this->is_chat_allowed = true;
						$this->current_user_membership_level = pmpro_getMembershipLevelForUser($current_user->ID)->name;
						
						// Get ids of the pages that the User is allowed to access
						if (function_exists('pmpro_getMembershipLevelsForUser')){
							$my_pages = array();
							$member_pages = array();
							$levels = pmpro_getMembershipLevelsForUser($current_user->ID);

							if ($levels) {

								foreach ($levels as $key => $level) {
									
									// Get restricted posts for level, make sure the object contains membership info
									if (isset($level->ID)) {
										global $wpdb;
										
										$sql = $wpdb->prepare("
											SELECT page_id
											FROM {$wpdb->pmpro_memberships_pages}
											WHERE membership_id = %d",
											$level->ID
										);

										$member_pages = $wpdb->get_col($sql);
										$my_pages = array_unique(array_merge($my_pages, $member_pages));
									}
								}
							}

							$this->my_pages = $my_pages;
						}
					}
				}
			}

			// Print the gathered settings into the page
			$resp = '<div class="wplc-pmpro settings-box hidden">';
			$resp .=    '<p id="wplc_pmpro_is_chat_for_members_only">'.json_encode($this->is_chat_for_members_only).'</p>';
			$resp .=    '<p id="wplc_pmpro_is_chat_allowed">'.json_encode($this->is_chat_allowed).'</p>';
			$resp .=    '<p id="wplc_pmpro_u_memb_level">'.json_encode($this->current_user_membership_level).'</p>';
			$resp .=    '<p id="wplc_pmpro_u_pages">'.json_encode($this->my_pages).'</p>';
			$resp .=    '<p id="wplc_pmpro_chat_not_allowed_explanation">'.$this->chat_not_allowed_explanation.'</p>';
			$resp .=    '<p id="wplc_pmpro_sign_up_redirect_url">'.$this->sign_up_redirect_url.'</p>';
			$resp .=    '<p id="wplc_pmpro_sign_up_btn_label">'.$this->sign_up_btn_label.'</p>';
			$resp .=    '<p id="wplc_pmpro_is_filter_chat_by_perm_level_enabled">'.json_encode($this->is_filter_chat_by_perm_level_enabled).'</p>';
			$resp .=    '<p id="wplc_pmpro_restricted_access_level_msg">'.$this->restricted_access_level_msg.'</p>';
			$resp .=    '<p id="wplc_pmpro_label_upgrade_user_level_btn">'.$this->upgrade_user_level_btn_label.'</p>';
			$resp .= '</div>';

			if ($this->is_chat_allowed) {
				$resp .= $button_content;
			} else {
				$resp .= $this->chat_not_allowed_explanation;
			}

			return $resp;
		} else {
			return $button_content;
		}
	}

	/**
	 * Print the page number on a settings box to improve decision making on wplc-pmpro-js
	 * @return string the footer defined in the WPLC + a hidden settings box
	 */
	function wplc_pmpro_get_page_info() {
		?>
			<div class="wplc-pmpro settings-box hidden">
				<p id="wplc_pmpro_pid"><?php echo the_id(); ?></p>
			</div>
		<?php
	}

}

$wplc_ext_pmpro = new WP_Live_Chat_Support_Ext_PMPro();