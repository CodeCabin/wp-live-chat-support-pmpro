/**
 * wplc-pmpro.js is a controller for the actions of the WPLC button that are related to the 
 * Paid Membership Pro plugin. 
 * 
 * This script is used to build the chat button according to the User settings, ensuring that all
 * access-level rules defined for the use of the chat, are going to be followed.
 * 
 * @author Diego M. Silva - (diegomsilva.com)
 * @copyright CodeCabin_ - (http://codecabin.co.za)
 * @version 0.0.1
 * @requires jquery
 * @license MIT
 */

function WPLC_PMPro_Plugin_Ext() {
	this._initialized = false;
}

WPLC_PMPro_Plugin_Ext.prototype = {

	PATTERN_ACCESS_LEVEL : "!!!access-level!!!",
	SELECTORS : {
		wplc_pmpro_pid: "#wplc_pmpro_pid",
		wplc_pmpro_u_memb_level: "#wplc_pmpro_u_memb_level",
		wplc_pmpro_u_pages: "#wplc_pmpro_u_pages",
		wplc_pmpro_is_chat_allowed: "#wplc_pmpro_is_chat_allowed",
		wplc_pmpro_is_chat_for_members_only: "#wplc_pmpro_is_chat_for_members_only",
		wplc_pmpro_is_filter_chat_by_perm_level_enabled: "#wplc_pmpro_is_filter_chat_by_perm_level_enabled",
		wplc_pmpro_chat_not_allowed_explanation: "#wplc_pmpro_chat_not_allowed_explanation",
		wplc_pmpro_sign_up_redirect_url: "#wplc_pmpro_sign_up_redirect_url",
		wplc_pmpro_sign_up_btn_label: "#wplc_pmpro_sign_up_btn_label",
		wplc_pmpro_restricted_access_level_msg: "#wplc_pmpro_restricted_access_level_msg",
		wplc_pmpro_label_upgrade_user_level_btn: "#wplc_pmpro_label_upgrade_user_level_btn",
		speeching_button: "#speeching_button",
		wplc_hovercard_bottom: "#wplc_hovercard_bottom",
		wplc_first_message: "#wplc_first_message",
		wplc_pmpro_no_chat_redirect_btn: "#wplc_pmpro_no_chat_redirect_btn"
	},

	currentPageId : "",
	currentUserMembLevel : "",
	currentUserMembPages : "",

	isChatAllowed : false,
	isFilterChatPerPermissionLevelEnabled : true,
	isChatForMembersOnly : false,

	chatNotAllowedExplanation : "",
	signUpRedirectUrl : "",
	signUpBtnLabel : "",

	restrictedAccessLevelMsg : "",
	upgradeUserLevelLabel : "",

	getRedirectButtonChatNotAllowed: function(classes) {
		try {
			var redirectBtnId = this.SELECTORS.wplc_pmpro_no_chat_redirect_btn.replace("#", "");
			return '<a id="'+ redirectBtnId +'" class="'+ classes +' wplc-pmpro-btn-redirect" href="'+ this.signUpRedirectUrl +'" >'+ this.signUpBtnLabel +'</a>';
		} catch(err) {
			console.log("Failed to getRedirectButtonChatNotAllowed " + err.message);
			return '';
		}
	},

	isUserAllowedToAccessCurrentPage: function() {
		try {
			var memberPages = JSON.parse(this.currentUserMembPages);
			var isAllowed = false;

			for (var i = 0; i < memberPages.length; i++) {
				var pageId = memberPages[i];

				if (currentPageId == pageId) {
					isAllowed = true;
				}
			}

			return isAllowed;
		} catch(err) {
			console.log("Failed to isUserAllowedToAccessCurrentPage " + err.message);
			return false;
		}
	},

	informConstraintsOfAccessLevel: function() {
		try {
			this.restrictedAccessLevelMsg = this.restrictedAccessLevelMsg.replace(this.PATTERN_ACCESS_LEVEL, "<b>" + this.currentUserMembLevel + "</b>");
			
			jQuery(this.SELECTORS.wplc_first_message).html(this.restrictedAccessLevelMsg);
			jQuery(this.SELECTORS.wplc_pmpro_no_chat_redirect_btn).html(this.upgradeUserLevelLabel);
		} catch(err) {
			console.log("Failed to informConstraintsOfAccessLevel " + err.message);
		}
	},

	eraseLatestSessionData: function() {
		localStorage.setItem('cid', '');
		localStorage.setItem('wplc_chat_status', '');
	},

	disableChatButtonAllowRedirect: function() {
		try {
			var classes = jQuery(this.SELECTORS.speeching_button).attr("class");

			// Create te redirect button using the style of the current theme
			jQuery(this.SELECTORS.wplc_hovercard_bottom).append(this.getRedirectButtonChatNotAllowed(classes));

			// Removes the start chat button
			jQuery(this.SELECTORS.speeching_button).remove();
		} catch(err) {
			console.log("Failed to disableChatButtonAllowRedirect " + err.message);
		}
	},

	initUI: function() {
		try {
			if (this.isChatForMembersOnly == 'true' && this.isChatAllowed != 'true') {
				this.disableChatButtonAllowRedirect();

			} else if (this.isFilterChatPerPermissionLevelEnabled == 'true' && !this.isUserAllowedToAccessCurrentPage()) {
				this.disableChatButtonAllowRedirect();
				this.informConstraintsOfAccessLevel();
			}
		} catch(err) {
			console.log("Failed to initUI " + err.message);
		}
	},

	initEvents: function() {
		var ctx = this;

		jQuery(this.SELECTORS.wplc_pmpro_no_chat_redirect_btn).on("click", function() {
			ctx.eraseLatestSessionData();
		});
	},

	initData: function() {
		try {
			this.currentPageId = jQuery(this.SELECTORS.wplc_pmpro_pid).text();
			this.currentUserMembLevel = jQuery(this.SELECTORS.wplc_pmpro_u_memb_level).text();
			this.currentUserMembPages = jQuery(this.SELECTORS.wplc_pmpro_u_pages).text();

			this.isChatAllowed = jQuery(this.SELECTORS.wplc_pmpro_is_chat_allowed).text();
			this.isChatForMembersOnly = jQuery(this.SELECTORS.wplc_pmpro_is_chat_for_members_only).text();
			this.isFilterChatPerPermissionLevelEnabled = jQuery(this.SELECTORS.wplc_pmpro_is_filter_chat_by_perm_level_enabled).text();
			
			this.chatNotAllowedExplanation = jQuery(this.SELECTORS.wplc_pmpro_chat_not_allowed_explanation).text();
			this.signUpRedirectUrl = jQuery(this.SELECTORS.wplc_pmpro_sign_up_redirect_url).text();
			this.signUpBtnLabel = jQuery(this.SELECTORS.wplc_pmpro_sign_up_btn_label).text();
			this.restrictedAccessLevelMsg = jQuery(this.SELECTORS.wplc_pmpro_restricted_access_level_msg).text();
			this.upgradeUserLevelLabel = jQuery(this.SELECTORS.wplc_pmpro_label_upgrade_user_level_btn).text();
		} catch(err) {
			console.log("Failed to initData " + err.message);
		}
	},

	init: function() {
		try {
			var ctx = this;

			jQuery(document).on("wplc_animation_done", function(e) {
				ctx.initData();
				ctx.initEvents();
				ctx.initUI();
			});
		} catch(err) {
			console.log("Failed to init " + err.message);
		}
	}

}

var wplcPmProExt = new WPLC_PMPro_Plugin_Ext();
wplcPmProExt.init();