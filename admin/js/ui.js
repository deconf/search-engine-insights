/*-
 * Author: Alin Marcu 
 * Author URI: https://deconf.com 
 * Copyright 2013 Alin Marcu 
 * License: GPLv2 or later 
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

"use strict";

jQuery( document ).ready( function () {

	var seiwp_ui = {
		action : 'seiwp_dismiss_notices',
		seiwp_security_dismiss_notices : seiwp_ui_data.security,
	}

	jQuery( "#seiwp-notice .notice-dismiss" ).on("click",  function () {
		jQuery.post( seiwp_ui_data.ajaxurl, seiwp_ui );
	} );

	if ( seiwp_ui_data.ed_bubble != '' ) {
		jQuery( '#toplevel_page_seiwp_settings li > a[href*="page=seiwp_errors_debugging"]' ).append( '&nbsp;<span class="awaiting-mod count-1"><span class="pending-count" style="padding:0 7px;">' + seiwp_ui_data.ed_bubble + '</span></span>' );
	}

} );