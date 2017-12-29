/**
 * wplc-admin-pmpro.js is a controller for the actions of the settings area of the WPLC-PMPro extension.
 * 
 * @author Diego M. Silva - (diegomsilva.com)
 * @copyright CodeCabin_ - (http://codecabin.co.za)
 * @version 0.0.1
 * @requires jquery
 * @license MIT
 */

function WPLC_PMPro_Plugin_Ext_Admin() {
	this._initialized = false;
}

WPLC_PMPro_Plugin_Ext_Admin.prototype = {

	SELECTORS : {
		wplc_pmpro_restricted_access_level_checkbox: "#wplc_pmpro_level_access_restriction_enable",
		wplc_pmpro_restricted_access_level_msg: "#wplc_pmpro_restricted_access_level_msg",
		wplc_pmpro_label_upgrade_user_level_btn: "#wplc_pmpro_label_upgrade_user_level_btn"
	},

	enableAccessLevelRestrictionFields: function() {
		try {
			jQuery(this.SELECTORS.wplc_pmpro_restricted_access_level_msg).removeAttr('disabled');
			jQuery(this.SELECTORS.wplc_pmpro_label_upgrade_user_level_btn).removeAttr('disabled');
		} catch(err) {
			console.log("Failed to enableAccessLevelRestrictionFields " + err.message);
		}
	},

	disableAccessLevelRestrictionFields: function() {
		try {
			jQuery(this.SELECTORS.wplc_pmpro_restricted_access_level_msg).attr('disabled', 'disabled');
			jQuery(this.SELECTORS.wplc_pmpro_label_upgrade_user_level_btn).attr('disabled', 'disabled');
		} catch(err) {
			console.log("Failed to disableAccessLevelRestrictionFields " + err.message);
		}
	},

	initEvents: function() {
		try {
			var ctx = this;
			
			jQuery(this.SELECTORS.wplc_pmpro_restricted_access_level_checkbox).on("click", function() {
				
				if (jQuery(this).attr("checked") == 'checked') {
					ctx.enableAccessLevelRestrictionFields();
				} else {
					ctx.disableAccessLevelRestrictionFields();
				}
			});

		} catch(err) {
			console.log("Failed to initEvents " + err.message);
		}
	},
	
	init: function() {
		try {
			var ctx = this;

			jQuery(document).ready(function() {
				ctx.initEvents();
			});
		} catch(err) {
			console.log("Failed to init " + err.message);
		}
	}

}

var wplcPmProExtAdmin = new WPLC_PMPro_Plugin_Ext_Admin();
wplcPmProExtAdmin.init();