<?php
/**
 * Author: Alin Marcu
 * Copyright 2017 Alin Marcu
 * Author URI: https://deconf.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
?>
<form name="input" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
	<table class="seiwp-settings-options">
		<tr>
			<td colspan="2" class="seiwp-settings-info">
						<?php echo __( "Use this link to get your <strong>one-time-use</strong> access code:", 'search-engine-insights' ) . ' <a href="' . $data['authUrl'] . '" id="gapi-access-code" target="_blank">' . __ ( "Get Access Code", 'search-engine-insights' ) . '</a>.'; ?>
			</td>
		</tr>
		<tr>
			<td class="seiwp-settings-title">
				<label for="seiwp_access_code" title="<?php _e("Use the red link to get your access code! You need to generate a new one each time you authorize!",'search-engine-insights')?>"><?php echo _e( "Access Code:", 'search-engine-insights' ); ?></label>
			</td>
			<td>
				<input type="text" id="seiwp_access_code" name="seiwp_access_code" value="" size="61" autocomplete="off" pattern=".\/.{30,}" required="required" title="<?php _e("Use the red link to get your access code! You need to generate a new one each time you authorize!",'search-engine-insights')?>">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<hr>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" class="button button-secondary" name="seiwp_authorize" value="<?php _e( "Save Access Code", 'search-engine-insights' ); ?>" />
			</td>
		</tr>
	</table>
</form>
