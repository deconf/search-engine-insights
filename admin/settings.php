<?php
/**
 * Author: Alin Marcu
 * Author URI: https://deconf.com
 * Copyright 2013 Alin Marcu
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
//@formatter:off
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Google\Service\Exception as GoogleServiceException;

final class SEIWP_Settings {

	private static function update_options( $who ) {
		$seiwp = SEIWP();
		$network_settings = false;
		$options = $seiwp->config->options; // Get current options
		if ( isset( $_POST['options']['seiwp_hidden'] ) && isset( $_POST['options'] ) && ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) && 'Reset' != $who ) {
			$new_options = $_POST['options'];
			if ( 'settings' == $who ) {
				$options['switch_profile'] = 0;
				$options['backend_item_reports'] = 0;
				$options['dashboard_widget'] = 0;
				if ( empty( $new_options['access_back'] ) ) {
					$new_options['access_back'][] = 'administrator';
				}
				$options['frontend_item_reports'] = 0;
				if ( empty( $new_options['access_front'] ) ) {
					$new_options['access_front'][] = 'administrator';
				}
			} else if ( 'setup' == $who ) {
				$options['user_api'] = 0;
			} else if ( 'network' == $who ) {
				$options['user_api'] = 0;
				$options['network_mode'] = 0;
				$network_settings = true;
			}
			$options = array_merge( $options, $new_options );
			$seiwp->config->options = $options;
			$seiwp->config->set_plugin_options( $network_settings );
		}
		return $options;
	}

	private static function navigation_tabs( $tabs ) {
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			echo "<a class='nav-tab' id='tab-$tab' href='#top#seiwp-$tab'>$name</a>";
		}
		echo '</h2>';
	}

	public static function settings() {

		$seiwp = SEIWP();

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = self::update_options( 'settings' );
		if ( isset( $_POST['options']['seiwp_hidden'] ) ) {
			$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Settings saved.", 'search-engine-insights' ) . "</p></div>";
			if ( ! ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) ) {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( ! $seiwp->config->options['site_jail'] || ! $seiwp->config->options['token'] ) {
			$message = sprintf( '<div class="error"><p>%s</p></div>', sprintf( __( 'Something went wrong, check %1$s or %2$s.', 'search-engine-insights' ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_errors_debugging', false ), __( 'Debug', 'search-engine-insights' ) ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_setup', false ), __( 'authorize the plugin', 'search-engine-insights' ) ) ) );
		}
		?>
<form name="seiwp_form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
	<div class="wrap">
			<?php echo "<h2>" . __( "Search Engine Insights - Settings", 'search-engine-insights' ) . "</h2>"; ?><hr>
	</div>
	<div id="poststuff" class="seiwp">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="settings-wrapper">
					<div class="inside">
					<?php if (isset($message)) echo $message; ?>
						<table class="seiwp-settings-options">
							<tr>
								<td colspan="2"><?php echo "<h2>" . __( "Backend Permissions", 'search-engine-insights' ) . "</h2>"; ?></td>
							</tr>
							<tr>
								<td class="roles seiwp-settings-title">
									<label for="access_back"><?php _e("Show stats to:", 'search-engine-insights' ); ?>
									</label>
								</td>
								<td class="seiwp-settings-roles">
									<table>
										<tr>
										<?php if ( ! isset( $wp_roles ) ) : ?>
											<?php $wp_roles = new WP_Roles(); ?>
										<?php endif; ?>
										<?php $i = 0; ?>
										<?php foreach ( $wp_roles->role_names as $role => $name ) : ?>
											<?php if ( 'subscriber' != $role ) : ?>
												<?php $i++; ?>
											<td>
												<label>
													<input type="checkbox" name="options[access_back][]" value="<?php echo $role; ?>" <?php if ( in_array($role,$options['access_back']) || 'administrator' == $role ) echo 'checked="checked"'; if ( 'administrator' == $role ) echo 'disabled="disabled"';?> /> <?php echo $name; ?>
												</label>
											</td>
											<?php endif; ?>
											<?php if ( 0 == $i % 4 ) : ?>
										</tr>
										<tr>
											<?php endif; ?>
										<?php endforeach; ?>
									</table>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="seiwp-settings-title">
									<div class="seiwp-togglegroup">
										<input type="checkbox" name="options[switch_profile]" value="1" id="switch_profile" <?php checked( $options['switch_profile'], 1 ); ?>>
										<label for="switch_profile">
									        <?php _e ( "enable Switch View functionality", 'search-engine-insights' );?>
									    </label>
										<div class="seiwp-onoffswitch pull-right" aria-hidden="true">
											<div class="seiwp-onoffswitch-label">
												<div class="seiwp-onoffswitch-inner"></div>
												<div class="seiwp-onoffswitch-switch"></div>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="seiwp-settings-title">
									<div class="seiwp-togglegroup">
										<input type="checkbox" name="options[backend_item_reports]" value="1" id="backend_item_reports" <?php checked( $options['backend_item_reports'], 1 ); ?>>
										<label for="backend_item_reports">
									        <?php _e ( "enable reports on Posts List and Pages List", 'search-engine-insights' );?>
									    </label>
										<div class="seiwp-onoffswitch pull-right" aria-hidden="true">
											<div class="seiwp-onoffswitch-label">
												<div class="seiwp-onoffswitch-inner"></div>
												<div class="seiwp-onoffswitch-switch"></div>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="seiwp-settings-title">
									<div class="seiwp-togglegroup">
										<input type="checkbox" name="options[dashboard_widget]" value="1" id="dashboard_widget" <?php checked( $options['dashboard_widget'], 1 ); ?>>
										<label for="dashboard_widget">
									        <?php _e ( "enable the main Dashboard Widget", 'search-engine-insights' );?>
									    </label>
										<div class="seiwp-onoffswitch pull-right" aria-hidden="true">
											<div class="seiwp-onoffswitch-label">
												<div class="seiwp-onoffswitch-inner"></div>
												<div class="seiwp-onoffswitch-switch"></div>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="2"><?php echo "<h2>" . __( "Frontend Permissions", 'search-engine-insights' ) . "</h2>"; ?></td>
							</tr>
							<tr>
								<td class="roles seiwp-settings-title">
									<label for="access_front"><?php _e("Show stats to:", 'search-engine-insights' ); ?>
									</label>
								</td>
								<td class="seiwp-settings-roles">
									<table>
										<tr>
										<?php if ( ! isset( $wp_roles ) ) : ?>
											<?php $wp_roles = new WP_Roles(); ?>
										<?php endif; ?>
										<?php $i = 0; ?>
										<?php foreach ( $wp_roles->role_names as $role => $name ) : ?>
											<?php if ( 'subscriber' != $role ) : ?>
												<?php $i++; ?>
												<td>
												<label>
													<input type="checkbox" name="options[access_front][]" value="<?php echo $role; ?>" <?php if ( in_array($role,$options['access_front']) || 'administrator' == $role ) echo 'checked="checked"'; if ( 'administrator' == $role ) echo 'disabled="disabled"';?> /><?php echo $name; ?>
												  </label>
											</td>
											<?php endif; ?>
											<?php if ( 0 == $i % 4 ) : ?>
										 </tr>
										<tr>
											<?php endif; ?>
										<?php endforeach; ?>
									</table>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="seiwp-settings-title">
									<div class="seiwp-togglegroup">
										<input type="checkbox" name="options[frontend_item_reports]" value="1" id="frontend_item_reports" <?php checked( $options['frontend_item_reports'], 1 ); ?>>
										<label for="frontend_item_reports">
									        <?php echo " ".__("enable web page reports on frontend", 'search-engine-insights' );?>
									    </label>
										<div class="seiwp-onoffswitch pull-right" aria-hidden="true">
											<div class="seiwp-onoffswitch-label">
												<div class="seiwp-onoffswitch-inner"></div>
												<div class="seiwp-onoffswitch-switch"></div>
											</div>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<hr><?php echo "<h2>" . __( "Google Maps API", 'search-engine-insights' ) . "</h2>"; ?></td>
							</tr>
							<tr>
								<td colspan="2" class="seiwp-settings-title">
									<?php echo __("Maps API Key:", 'search-engine-insights'); ?>
									<input type="text" style="text-align: center;" name="options[maps_api_key]" value="<?php echo esc_attr($options['maps_api_key']); ?>" size="50">
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<hr><?php echo "<h2>" . __( "Appearance", 'search-engine-insights' ) . "</h2>"; ?></td>
							</tr>
							<tr>
								<td class="seiwp-settings-title">
									<label for="theme_color"><?php _e("Chart Color:", 'search-engine-insights' ); ?></label>
								</td>
								<td>
									<input type="text" id="theme_color" class="theme_color" name="options[theme_color]" value="<?php echo esc_attr($options['theme_color']); ?>" size="10">
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<hr>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="submit">
									<input type="submit" name="Submit" class="button button-primary" value="<?php _e('Save Changes', 'search-engine-insights' ) ?>" />
								</td>
							</tr>
						</table>
						<input type="hidden" name="options[seiwp_hidden]" value="Y">
						<?php wp_nonce_field('seiwp_form','seiwp_security'); ?>
</form>
<?php
		SEIWP_Tools::load_view( 'admin/views/settings-sidebar.php', array() );
	}

	public static function errors_debugging() {
		$seiwp = SEIWP();

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$anonim = SEIWP_Tools::anonymize_options( $seiwp->config->options );

		$options = self::update_options( 'frontend' );
		if ( ! $seiwp->config->options['site_jail'] || ! $seiwp->config->options['token'] ) {
			$message = sprintf( '<div class="error"><p>%s</p></div>', sprintf( __( 'Something went wrong, check %1$s or %2$s.', 'search-engine-insights' ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_errors_debugging', false ), __( 'Debug', 'search-engine-insights' ) ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_setup', false ), __( 'authorize the plugin', 'search-engine-insights' ) ) ) );
		}
		?>
<div class="wrap">
		<?php echo "<h2>" . __( "Search Engine Insights - Debug", 'search-engine-insights' ) . "</h2>"; ?>
</div>
<div id="poststuff" class="seiwp">
	<div id="post-body" class="metabox-holder columns-2">
		<div id="post-body-content">
			<div class="settings-wrapper">
				<div class="inside">
						<?php if (isset($message)) echo $message; ?>
						<?php $tabs = array( 'errors' => __( "Errors", 'search-engine-insights' ), 'config' => __( "Plugin Settings", 'search-engine-insights' ), 'sysinfo' => __( "System", 'search-engine-insights' ) ); ?>
						<?php self::navigation_tabs( $tabs ); ?>
						<div id="seiwp-errors">
						<table class="seiwp-settings-logdata">
							<tr>
								<td>
									<?php echo "<h2>" . __( "Error Details", 'search-engine-insights' ) . "</h2>"; ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php $errors_count = SEIWP_Tools::get_cache( 'errors_count' ); ?>
									<pre class="seiwp-settings-logdata"><?php echo '<span>' . __("Count: ", 'search-engine-insights') . '</span>' . (int)$errors_count;?></pre>
									<?php $errors = print_r( SEIWP_Tools::get_cache( 'last_error' ), true ) ? esc_html( print_r( SEIWP_Tools::get_cache( 'last_error' ), true ) ) : ''; ?>
									<pre class="seiwp-settings-logdata"><?php echo '<span>' . __("Last Error: ", 'search-engine-insights') . '</span>' . "\n" . $errors;?></pre>
									<pre class="seiwp-settings-logdata"><?php echo '<span>' . __("GAPI Error: ", 'search-engine-insights') . '</span>'; echo "\n" . esc_html( print_r( SEIWP_Tools::get_cache( 'gapi_errors' ), true ) ) ?></pre>
									<br />
									<hr>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo "<h2>" . __( "Sampled Data", 'search-engine-insights' ) . "</h2>"; ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php $sampling = SEIWP_TOOLS::get_cache( 'sampleddata' ); ?>
									<?php if ( $sampling ) :?>
									<?php printf( __( "Last Detected on %s.", 'search-engine-insights' ), '<strong>'. $sampling['date'] . '</strong>' );?>
									<br />
									<?php printf( __( "The report was based on %s of sessions.", 'search-engine-insights' ), '<strong>'. $sampling['percent'] . '</strong>' );?>
									<br />
									<?php printf( __( "Sessions ratio: %s.", 'search-engine-insights' ), '<strong>'. $sampling['sessions'] . '</strong>' ); ?>
									<?php else :?>
									<?php _e( "None", 'search-engine-insights' ); ?>
									<?php endif;?>
								</td>
							</tr>
						</table>
					</div>
					<div id="seiwp-config">
						<table class="seiwp-settings-options">
							<tr>
								<td><?php echo "<h2>" . __( "Plugin Configuration", 'search-engine-insights' ) . "</h2>"; ?></td>
							</tr>
							<tr>
								<td>
									<pre class="seiwp-settings-logdata"><?php echo esc_html(print_r($anonim, true));?></pre>
									<br />
									<hr>
								</td>
							</tr>
						</table>
					</div>
					<div id="seiwp-sysinfo">
						<table class="seiwp-settings-options">
							<tr>
								<td><?php echo "<h2>" . __( "System Information", 'search-engine-insights' ) . "</h2>"; ?></td>
							</tr>
							<tr>
								<td>
									<pre class="seiwp-settings-logdata"><?php echo esc_html(SEIWP_Tools::system_info());?></pre>
									<br />
									<hr>
								</td>
							</tr>
						</table>
					</div>
	<?php
			SEIWP_Tools::load_view( 'admin/views/settings-sidebar.php', array() );
	}

	public static function setup() {
		$seiwp = SEIWP();

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = self::update_options( 'setup' );
		printf( '<div id="gapi-warning" class="updated"><p>%1$s <a href="https://deconf.com/search-engine-insights/">%2$s</a></p></div>', __( 'Loading the required libraries. If this results in a blank screen or a fatal error, try this solution:', 'search-engine-insights' ), __( 'Library conflicts between WordPress plugins', 'search-engine-insights' ) );
		if ( null === $seiwp->gapi_controller ) {
			$seiwp->gapi_controller = new SEIWP_GAPI_Controller();
		}
		echo '<script type="text/javascript">jQuery("#gapi-warning").hide()</script>';

		if ( isset( $_REQUEST['seiwp_access_code'] ) ) {
			if ( $_REQUEST['seiwp_access_code'] != get_option( 'seiwp_redeemed_code' ) ) {
				try {
					$seiwp_access_code = sanitize_text_field( $_REQUEST['seiwp_access_code'] );
					update_option( 'seiwp_redeemed_code', $seiwp_access_code );
					SEIWP_Tools::delete_cache( 'gapi_errors' );
					SEIWP_Tools::delete_cache( 'last_error' );

					$token = $seiwp->gapi_controller->authenticate( $seiwp_access_code );

					$array_token = (array)$token;

					$seiwp->gapi_controller->client->setAccessToken( $array_token );

					$seiwp->config->options['token'] = $seiwp->gapi_controller->client->getAccessToken();

					$seiwp->config->set_plugin_options();

					$options = self::update_options( 'setup' );
					$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Plugin authorization succeeded.", 'search-engine-insights' ) . "</p></div>";
					if ( $seiwp->config->options['token'] && $seiwp->gapi_controller->client->getAccessToken() ) {
						$sites = $seiwp->gapi_controller->get_sites_info();
						if ( is_array( $sites ) && ! empty( $sites ) ) {
							$seiwp->config->options['sites_list'] = $sites;
							if ( ! $seiwp->config->options['site_jail'] ) {
								$site = SEIWP_Tools::guess_default_domain( $sites );
								$seiwp->config->options['site_jail'] = $site;
							}
							$seiwp->config->set_plugin_options();
							$options = self::update_options( 'setup' );
						}
					}
				} catch ( GoogleServiceException $e ) {
					$timeout = $seiwp->gapi_controller->get_timeouts( 'midnight' );
					SEIWP_Tools::set_error( $e, $timeout );
				} catch ( Exception $e ) {
					$timeout = $seiwp->gapi_controller->get_timeouts( 'midnight' );
					SEIWP_Tools::set_error( $e, $timeout );
					$seiwp->gapi_controller->reset_token();
				}
			} else {
					$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You can only use the access code <strong>once</strong>, please generate a <strong>new access</strong> code following the instructions!", 'search-engine-insights' ) . ".</p></div>";
			}
		}
		if ( isset( $_POST['Clear'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				SEIWP_Tools::clear_cache();
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Cleared Cache.", 'search-engine-insights' ) . "</p></div>";
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['Reset'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				$seiwp->gapi_controller->reset_token(true);
				SEIWP_Tools::clear_cache();
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Token Reseted and Revoked.", 'search-engine-insights' ) . "</p></div>";
				$options = self::update_options( 'Reset' );
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['Reset_Err'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {

				if ( SEIWP_Tools::get_cache( 'gapi_errors' ) || SEIWP_Tools::get_cache( 'last_error' ) ) {

					$info = SEIWP_Tools::system_info();
					$info .= 'SEIWP Version: ' . SEIWP_CURRENT_VERSION;

					$sep = "\n---------------------------\n";
					$error_report = SEIWP_Tools::get_cache( 'last_error' );
					$error_report .= $sep . print_r( SEIWP_Tools::get_cache( 'gapi_errors' ), true );
					$error_report .= $sep . SEIWP_Tools::get_cache( 'errors_count' );
					$error_report .= $sep . $info;

					$error_report = urldecode( $error_report );

					$url = SEIWP_ENDPOINT_URL . 'seiwp-report.php';
					/* @formatter:off */
					$response = wp_remote_post( $url, array(
							'method' => 'POST',
							'timeout' => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'blocking' => true,
							'headers' => array(),
							'body' => array( 'error_report' => $error_report ),
							'cookies' => array()
						)
					);
				}

				/* @formatter:on */
				SEIWP_Tools::delete_cache( 'last_error' );
				SEIWP_Tools::delete_cache( 'gapi_errors' );
				delete_option( 'seiwp_got_updated' );
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "All errors reseted.", 'search-engine-insights' ) . "</p></div>";
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['options']['seiwp_hidden'] ) && ! isset( $_POST['Verify_Property'] ) && ! isset( $_POST['Add_Property'] ) && ! isset( $_POST['Clear'] ) && ! isset( $_POST['Reset'] ) && ! isset( $_POST['Reset_Err'] ) ) {
			$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Settings saved.", 'search-engine-insights' ) . "</p></div>";
			if ( ! ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) ) {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['Add_Property'] ) ) {
			if ( $seiwp->gapi_controller->add_property() ){
				$sites = $seiwp->gapi_controller->get_sites_info();
				if ( is_array( $sites ) && ! empty( $sites ) ) {
					$seiwp->config->options['sites_list'] = $sites;
					$site = SEIWP_Tools::guess_default_domain( $sites );
					$seiwp->config->options['site_jail'] = $site;
					$options = self::update_options( 'setup' );
				}
			}
		}
		if ( isset( $_POST['Verify_Property'] ) ) {
			if ( false == $seiwp->gapi_controller->verify_property() ){
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "Unable to verify site. Please disable your cache plugin or clear the page cache before retrying.", 'search-engine-insights' ) . "</p></div>";
			} else {
				$sites = $seiwp->gapi_controller->get_sites_info();
				if ( is_array( $sites ) && ! empty( $sites ) ) {
					$seiwp->config->options['sites_list'] = $sites;
					$site = SEIWP_Tools::guess_default_domain( $sites );
					$seiwp->config->options['site_jail'] = $site;
					$options = self::update_options( 'setup' );
				}
			}
		}
		if ( isset( $_POST['Hide'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				$message = "<div class='updated' id='seiwp-action'><p>" . __( "All other domains/properties were removed.", 'search-engine-insights' ) . "</p></div>";
				$lock_profile = SEIWP_Tools::get_selected_site( $seiwp->config->options['sites_list'], $seiwp->config->options['site_jail'] );
				$seiwp->config->options['sites_list'] = array( $lock_profile );
				$seiwp->config->options['sites_list_locked'] = array( $lock_profile );
				$options = self::update_options( 'setup' );
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		?>
	<div class="wrap">
	<?php echo "<h2>" . __( "Search Engine Insights - Setup", 'search-engine-insights' ) . "</h2>"; ?>
					<hr>
					</div>
					<div id="poststuff" class="seiwp">
						<div id="post-body" class="metabox-holder columns-2">
							<div id="post-body-content">
								<div class="settings-wrapper">
									<div class="inside">
										<?php if ( ( $seiwp->gapi_controller->gapi_errors_handler() || SEIWP_Tools::get_cache( 'last_error' ) )  && strpos(SEIWP_Tools::get_cache( 'last_error' ), '-27') === false ) : ?>
													<?php $message = sprintf( '<div class="error"><p>%s</p></div>', sprintf( __( 'Something went wrong, check %1$s or %2$s.', 'search-engine-insights' ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_errors_debugging', false ), __( 'Debug', 'search-engine-insights' ) ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_setup', false ), __( 'authorize the plugin', 'search-engine-insights' ) ) ) );?>
										<?php endif;?>
										<?php if ( isset( $message ) ) :?>
											<?php echo $message;?>
										<?php endif; ?>
										<form name="seiwp_form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
											<input type="hidden" name="options[seiwp_hidden]" value="Y">
											<?php wp_nonce_field('seiwp_form','seiwp_security'); ?>
											<table class="seiwp-settings-options">
												<tr>
													<td colspan="2">
														<?php echo "<h2>" . __( "Search Console Authorization", 'search-engine-insights' ) . "</h2>";?>
													</td>
												</tr>
												<tr>
													<td colspan="2" class="seiwp-settings-info">
														<?php printf(__('You need to create a %1$s and follow %2$s before proceeding to authorization.', 'search-engine-insights'), sprintf('<a href="%1$s" target="_blank">%2$s</a>', 'https://deconf.com/creating-a-google-search-console-account/', __("Google Search Console account", 'search-engine-insights')), sprintf('<a href="%1$s" target="_blank">%2$s</a>', 'https://deconf.com/search-engine-insights/', __("this tutorial", 'search-engine-insights')));?>
													</td>
												</tr>
												  <?php if (! $options['token'] || ($options['user_api']  && ! $options['network_mode'])) : ?>
												<tr>
													<td colspan="2" class="seiwp-settings-info">
														<input name="options[user_api]" type="checkbox" id="user_api" value="1" <?php checked( $options['user_api'], 1 ); ?> onchange="this.form.submit()" <?php echo ($options['network_mode'])?'disabled="disabled"':''; ?> /><?php echo " ".__("developer mode (requires advanced API knowledge)", 'search-engine-insights' );?>
													</td>
												</tr>
												  <?php endif; ?>
												  <?php if ($options['user_api']  && ! $options['network_mode']) : ?>
												<tr>
													<td class="seiwp-settings-title">
														<label for="options[client_id]"><?php _e("Client ID:", 'search-engine-insights'); ?></label>
													</td>
													<td>
														<input type="text" name="options[client_id]" value="<?php echo esc_attr($options['client_id']); ?>" size="40" required="required">
													</td>
												</tr>
												<tr>
													<td class="seiwp-settings-title">
														<label for="options[client_secret]"><?php _e("Client Secret:", 'search-engine-insights'); ?></label>
													</td>
													<td>
														<input type="text" name="options[client_secret]" value="<?php echo esc_attr($options['client_secret']); ?>" size="40" required="required">
														<input type="hidden" name="options[seiwp_hidden]" value="Y">
														<?php wp_nonce_field('seiwp_form','seiwp_security'); ?>
													</td>
												</tr>
												  <?php endif; ?>
												  <?php if ( $options['token'] ) : ?>
												<tr>
													<td colspan="2">
														<input type="submit" name="Reset" class="button button-secondary" value="<?php _e( "Clear Authorization", 'search-engine-insights' ); ?>" <?php echo $options['network_mode']?'disabled="disabled"':''; ?> />
														<input type="submit" name="Clear" class="button button-secondary" value="<?php _e( "Clear Cache", 'search-engine-insights' ); ?>" />
														<input type="submit" name="Reset_Err" class="button button-secondary" value="<?php _e( "Report & Reset Errors", 'search-engine-insights' ); ?>" />
													</td>
												</tr>
												<tr>
													<td colspan="2">
														<hr>
													</td>
												</tr>
												<?php if ( !$options['network_mode'] ) : ?>
												<tr>
													<td colspan="2"><?php echo "<h2>" . __( "Site Verification", 'search-engine-insights' ) . "</h2>"; ?></td>
												</tr>
												<tr>
													<td class="seiwp-settings-title">
														<label for="site_jail"><?php _e("Default Site URL:", 'search-engine-insights' ); ?></label>
													</td>
													<td>
														<?php echo '<strong>' . SEIWP_SITE_URL . '</strong>'; ?>
													 </td>
												</tr>
												<tr>
													<td class="seiwp-settings-title">
														<label for="site_jail"><?php _e("Status:", 'search-engine-insights' ); ?></label>
													</td>
													<td>
														<?php if ( empty (SEIWP_Tools::get_selected_site( $seiwp->config->options['sites_list'], SEIWP_SITE_URL) ) ) : ?>
													 	<?php echo  '<span class="seiwp-red">' . __("Not found", 'search-engine-insights' ) . '</span>'; ?>
															<?php $flag = false; ?>
													 <?php else: ?>
														 <?php $flag = false; ?>
										 				<?php foreach ( $seiwp->config->options['sites_list'] as $items ) : ?>
																<?php if ( ( $items[0] == SEIWP_SITE_URL ) && ( $items[1] <> 'siteUnverifiedUser' ) ) : ?>
																	<?php $flag = true; ?>
																<?php endif; ?>
															<?php endforeach; ?>
															<?php if ( $flag ) : ?>
														 	<?php echo  '<span class="seiwp-green">' . __("Verified", 'search-engine-insights' ) . '</span>'; ?>
														 <?php else : ?>
														 	<?php echo  '<span class="seiwp-red">' . __("Unverified", 'search-engine-insights' ) . '</span>'; ?>
														 <?php endif; ?>
													 <?php endif; ?>
													 </td>
												</tr>
												<tr>
													<td class="seiwp-settings-title">
													</td>
													<td>
														<?php if ( empty (SEIWP_Tools::get_selected_site( $seiwp->config->options['sites_list'], SEIWP_SITE_URL ) ) && !$options['sites_list_locked'] ) : ?>
															<input type="submit" name="Add_Property" class="button button-secondary" value="<?php _e( "Add site", 'search-engine-insights' ); ?>" />
														<?php elseif ( !$flag && !$options['sites_list_locked'] ) : ?>
														 <input type="submit" name="Verify_Property" class="button button-secondary" value="<?php _e( "Verify Site", 'search-engine-insights' ); ?>" />
														<?php endif; ?>
													 </td>
												</tr>
												<tr>
													<td colspan="2">
														<hr>
													</td>
												</tr>
												<?php endif; ?>
												<tr>
													<td colspan="2"><?php echo "<h2>" . __( "Default Property", 'search-engine-insights' ) . "</h2>"; ?></td>
												</tr>
												<tr>
													<td class="seiwp-settings-title">
														<label for="site_jail"><?php _e("Select Property:", 'search-engine-insights' ); ?></label>
													</td>
													<td>
														<select id="site_jail" <?php disabled(empty($options['sites_list']) || 1 == count($options['sites_list']), true); ?> onchange="this.form.submit()" name="options[site_jail]">
															<?php if ( ! empty( $options['sites_list'] ) ) : ?>
																	<?php foreach ( $options['sites_list'] as $items ) : ?>
																		<?php if ( $items[0] ) : ?>
																			<option <?php disabled($items[1],'siteUnverifiedUser') ?> value="<?php echo esc_attr( $items[0] ); ?>" <?php selected( $items[0], $options['site_jail'] ); ?> title="<?php _e( "Site URL:", 'search-engine-insights' ); ?> <?php echo esc_attr( $items[0] ); ?>">
																				<?php echo esc_html( $items[0] )?>
																			</option>
																		<?php endif; ?>
																	<?php endforeach; ?>
															<?php else : ?>
																	<option value=""><?php _e( 'Property not found', 'search-engine-insights' ); ?></option>
															<?php endif; ?>
														</select>
														<?php if ( count( $options['sites_list'] ) > 1 ) : ?>
														&nbsp;<input type="submit" name="Hide" class="button button-secondary" value="<?php _e( "Lock Selection", 'search-engine-insights' ); ?>" />
														<?php endif; ?>
														<?php if ( empty( $options['sites_list'] ) ) : ?>
															<input type="submit" name="Add_Property" class="button button-secondary" value="<?php _e( "Add & Verify your site", 'search-engine-insights' ); ?>" />
														<?php endif; ?>
													 </td>
												</tr>
												<?php if ( $options['site_jail'] ) :	?>
												<tr>
													<td class="seiwp-settings-title"></td>
													<td>
													<?php if ( ! empty( $options['sites_list'] ) ) : ?>
															<?php $site_info = SEIWP_Tools::get_selected_site( $seiwp->config->options['sites_list'], $seiwp->config->options['site_jail'] ); ?>
															<?php $permission = ($site_info[1] == 'siteUnverifiedUser') ? '<span class="seiwp-red">' . esc_html( $site_info[1] ) . '</span>' :  esc_html( $site_info[1] )?>
															<pre><?php echo __( "Property URL:", 'search-engine-insights' ) . "\t" . esc_html( $site_info[0] ) . "<br />" . __( "Permission:", 'search-engine-insights' ) . "\t" . $permission?></pre>
												 <?php endif; ?>
													</td>
												</tr>
												<?php endif; ?>
												<tr>
													<td colspan="2">
														<hr>
													</td>
												</tr>
												<?php else : ?>
												<tr>
													<td colspan="2">
														<hr>
													</td>
												</tr>
												<tr>
													<td colspan="2">
													 <?php $auth = $seiwp->gapi_controller->client->createAuthUrl();?>
														<button type="submit" class="button button-secondary" formaction="<?php echo esc_url_raw( $auth ); ?>"><?php _e( "Authorize Plugin", 'search-engine-insights' ); ?></button>
														<button type="submit" name="Clear" class="button button-secondary"><?php _e( "Clear Cache", 'search-engine-insights' ); ?></button>
													</td>
												</tr>
												<tr>
													<td colspan="2">
														<hr>
													</td>
												</tr>
											</table>
										</form>
				<?php SEIWP_Tools::load_view( 'admin/views/settings-sidebar.php', array() ); ?>
				<?php return; ?>
			<?php endif; ?>
											</table>
										</form>
			<?php

			SEIWP_Tools::load_view( 'admin/views/settings-sidebar.php', array() );
	}

	// Network Settings
	public static function setup_network() {
		$seiwp = SEIWP();

		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
		$options = self::update_options( 'network' );
		/*
		 * Include GAPI
		 */
		echo '<div id="gapi-warning" class="updated"><p>' . __( 'Loading the required libraries. If this results in a blank screen or a fatal error, try this solution:', 'search-engine-insights' ) . ' <a href="https://deconf.com/search-engine-insights/">Library conflicts between WordPress plugins</a></p></div>';

		if ( null === $seiwp->gapi_controller ) {
			$seiwp->gapi_controller = new SEIWP_GAPI_Controller();
		}

		echo '<script type="text/javascript">jQuery("#gapi-warning").hide()</script>';
		if ( isset( $_REQUEST['seiwp_access_code'] ) ) {
			if ( $_REQUEST['seiwp_access_code'] != get_option( 'seiwp_redeemed_code' ) ) {
				try {
					$seiwp_access_code = sanitize_text_field( $_REQUEST['seiwp_access_code'] );
					update_option( 'seiwp_redeemed_code', $seiwp_access_code );
					SEIWP_Tools::delete_cache( 'gapi_errors' );
					SEIWP_Tools::delete_cache( 'last_error' );

					$token = $seiwp->gapi_controller->authenticate( $seiwp_access_code );
					$array_token = (array)$token;
					$seiwp->gapi_controller->client->setAccessToken( $array_token );
					$seiwp->config->options['token'] = $seiwp->gapi_controller->client->getAccessToken();

					$seiwp->config->set_plugin_options( true );
					$options = self::update_options( 'network' );
					$message = "<div class='updated' id='seiwp-action'><p>" . __( "Plugin authorization succeeded.", 'search-engine-insights' ) . "</p></div>";
					if ( is_multisite() ) { // Cleanup errors on the entire network
						foreach ( SEIWP_Tools::get_sites( array( 'number' => apply_filters( 'seiwp_sites_limit', 100 ) ) ) as $blog ) {
							switch_to_blog( $blog['blog_id'] );
							SEIWP_Tools::delete_cache( 'last_error' );
							SEIWP_Tools::delete_cache( 'gapi_errors' );
							restore_current_blog();
						}
					} else {
						SEIWP_Tools::delete_cache( 'last_error' );
						SEIWP_Tools::delete_cache( 'gapi_errors' );
					}
					if ( $seiwp->config->options['token'] && $seiwp->gapi_controller->client->getAccessToken() ) {
						$sites = $seiwp->gapi_controller->get_sites_info();
						if ( is_array( $sites ) && ! empty( $sites ) ) {
							$seiwp->config->options['sites_list'] = $sites;
							if ( isset( $seiwp->config->options['site_jail'] ) && ! $seiwp->config->options['site_jail'] ) {
								$site = SEIWP_Tools::guess_default_domain( $sites );
								$seiwp->config->options['site_jail'] = $site;
							}
							$seiwp->config->set_plugin_options( true );
							$options = self::update_options( 'network' );
						}
					}
				} catch ( GoogleServiceException $e ) {
					$timeout = $seiwp->gapi_controller->get_timeouts( 'midnight' );
					SEIWP_Tools::set_error( $e, $timeout );
					$seiwp->gapi_controller->reset_token();
				} catch ( Exception $e ) {
					$timeout = $seiwp->gapi_controller->get_timeouts( 'midnight' );
					SEIWP_Tools::set_error( $e, $timeout );
					$seiwp->gapi_controller->reset_token();
				}
			} else {
					$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You can only use the access code once.", 'search-engine-insights' ) . "!</p></div>";
			}
		}
		if ( isset( $_POST['Refresh'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				$seiwp->config->options['sites_list'] = array();
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Properties refreshed.", 'search-engine-insights' ) . "</p></div>";
				$options = self::update_options( 'network' );
				if ( $seiwp->config->options['token'] && $seiwp->gapi_controller->client->getAccessToken() ) {
					if ( ! empty( $seiwp->config->options['sites_list'] ) ) {
						$sites = $seiwp->config->options['sites_list'];
					} else {
						$sites = $seiwp->gapi_controller->get_sites_info();
					}
					if ( $sites ) {
						$seiwp->config->options['sites_list'] = $sites;
						if ( isset( $seiwp->config->options['site_jail'] ) && ! $seiwp->config->options['site_jail'] ) {
							$site = SEIWP_Tools::guess_default_domain( $sites );
							$seiwp->config->options['site_jail'] = $site;
						}
						$seiwp->config->set_plugin_options( true );
						$options = self::update_options( 'network' );
					}
				}
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['Clear'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				SEIWP_Tools::clear_cache();
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Cleared Cache.", 'search-engine-insights' ) . "</p></div>";
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['Reset'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				$seiwp->gapi_controller->reset_token(true);
				SEIWP_Tools::clear_cache();
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Token Reseted and Revoked.", 'search-engine-insights' ) . "</p></div>";
				$options = self::update_options( 'Reset' );
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['options']['seiwp_hidden'] ) && ! isset( $_POST['Clear'] ) && ! isset( $_POST['Reset'] ) && ! isset( $_POST['Refresh'] ) ) {
			$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "Settings saved.", 'search-engine-insights' ) . "</p></div>";
			if ( ! ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) ) {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		if ( isset( $_POST['Hide'] ) ) {
			if ( isset( $_POST['seiwp_security'] ) && wp_verify_nonce( $_POST['seiwp_security'], 'seiwp_form' ) ) {
				$message = "<div class='updated' id='seiwp-autodismiss'><p>" . __( "All other domains/properties were removed.", 'search-engine-insights' ) . "</p></div>";
				$lock_profile = SEIWP_Tools::get_selected_site( $seiwp->config->options['sites_list'], $seiwp->config->options['site_jail'] );
				$seiwp->config->options['sites_list'] = array( $lock_profile );
				$options = self::update_options( 'network' );
			} else {
				$message = "<div class='error' id='seiwp-autodismiss'><p>" . __( "You don’t have permission to do this.", 'search-engine-insights' ) . "</p></div>";
			}
		}
		?>
<div class="wrap">
											<h2><?php _e( "Search Engine Insights - Setup", 'search-engine-insights' );?></h2>
											<hr>
										</div>
										<div id="poststuff" class="seiwp">
											<div id="post-body" class="metabox-holder columns-2">
												<div id="post-body-content">
													<div class="settings-wrapper">
														<div class="inside">
					<?php if ( ( $seiwp->gapi_controller->gapi_errors_handler() || SEIWP_Tools::get_cache( 'last_error' ) )  && strpos(SEIWP_Tools::get_cache( 'last_error' ), '-27') === false ) : ?>
						<?php $message = sprintf( '<div class="error"><p>%s</p></div>', sprintf( __( 'Something went wrong, check %1$s or %2$s.', 'search-engine-insights' ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_errors_debugging', false ), __( 'Debug', 'search-engine-insights' ) ), sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'seiwp_settings', false ), __( 'authorize the plugin', 'search-engine-insights' ) ) ) );?>
					<?php endif; ?>
						<?php if ( isset( $message ) ) : ?>
							<?php echo $message; ?>
						<?php endif; ?>
					<form name="seiwp_form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
																<input type="hidden" name="options[seiwp_hidden]" value="Y">
						<?php wp_nonce_field('seiwp_form','seiwp_security'); ?>
						<table class="seiwp-settings-options">
																	<tr>
																		<td colspan="2">
								<?php echo "<h2>" . __( "Network Setup", 'search-engine-insights' ) . "</h2>"; ?>
								</td>
																	</tr>
																	<tr>
																		<td colspan="2" class="seiwp-settings-title">
																			<div class="seiwp-togglegroup">
																				<input type="checkbox" name="options[network_mode]" value="1" id="network_mode" <?php checked( $options['network_mode'], 1); ?> onchange="this.form.submit()">
																				<label for="network_mode">
																			        <?php echo " ".__("use a single Google Search Console account for the entire network", 'search-engine-insights' );?>
																			    </label>
																				<div class="seiwp-onoffswitch pull-right" aria-hidden="true">
																					<div class="seiwp-onoffswitch-label">
																						<div class="seiwp-onoffswitch-inner"></div>
																						<div class="seiwp-onoffswitch-switch"></div>
																					</div>
																				</div>
																			</div>
																		</td>
																	</tr>
							<?php if ($options['network_mode']) : ?>
							<tr>
																		<td colspan="2">
																			<hr>
																		</td>
																	</tr>
																	<tr>
																		<td colspan="2"><?php echo "<h2>" . __( "Search Console Authorization", 'search-engine-insights' ) . "</h2>"; ?></td>
																	</tr>
																	<tr>
																		<td colspan="2" class="seiwp-settings-info">
																			<?php printf(__('You need to create a %1$s and follow %2$s before proceeding to authorization.', 'search-engine-insights'), sprintf('<a href="%1$s" target="_blank">%2$s</a>', 'https://deconf.com/creating-a-google-search-console-account/', __("Google Search Console account", 'search-engine-insights')), sprintf('<a href="%1$s" target="_blank">%2$s</a>', 'https://deconf.com/search-engine-insights/', __("this tutorial", 'search-engine-insights')));?>
																		</td>
																	</tr>
								<?php if ( ! $options['token'] || $options['user_api'] ) : ?>
								<tr>
																		<td colspan="2" class="seiwp-settings-info">
																			<input name="options[user_api]" type="checkbox" id="user_api" value="1" <?php checked( $options['user_api'], 1 ); ?> onchange="this.form.submit()" /><?php echo " ".__("developer mode (requires advanced API knowledge)", 'search-engine-insights' );?>
								</td>
																	</tr>
								<?php endif; ?>
							<?php if ( $options['user_api'] ) : ?>
							<tr>
																		<td class="seiwp-settings-title">
																			<label for="options[client_id]"><?php _e("Client ID:", 'search-engine-insights'); ?>
									</label>
																		</td>
																		<td>
																			<input type="text" name="options[client_id]" value="<?php echo esc_attr($options['client_id']); ?>" size="40" required="required">
																		</td>
																	</tr>
																	<tr>
																		<td class="seiwp-settings-title">
																			<label for="options[client_secret]"><?php _e("Client Secret:", 'search-engine-insights'); ?>
									</label>
																		</td>
																		<td>
																			<input type="text" name="options[client_secret]" value="<?php echo esc_attr($options['client_secret']); ?>" size="40" required="required">
																			<input type="hidden" name="options[seiwp_hidden]" value="Y">
																			<?php wp_nonce_field('seiwp_form','seiwp_security'); ?>
								</td>
																	</tr>
							<?php endif; ?>
							<?php if ( $options['token'] ) : ?>
							<tr>
																		<td colspan="2">
																			<input type="submit" name="Reset" class="button button-secondary" value="<?php _e( "Clear Authorization", 'search-engine-insights' ); ?>" />
																			<input type="submit" name="Clear" class="button button-secondary" value="<?php _e( "Clear Cache", 'search-engine-insights' ); ?>" />
																			<input type="submit" name="Refresh" class="button button-secondary" value="<?php _e( "Refresh Properties", 'search-engine-insights' ); ?>" />
																		</td>
																	</tr>
																	<tr>
																		<td colspan="2">
																			<hr>
																		</td>
																	</tr>
																	<tr>
																		<td colspan="2">
								<?php echo "<h2>" . __( "Properties Settings", 'search-engine-insights' ) . "</h2>"; ?>
								</td>
																	</tr>
							<?php if ( isset( $options['network_tableid'] ) ) : ?>
								<?php $options['network_tableid'] = json_decode( json_encode( $options['network_tableid'] ), false ); ?>
							<?php endif; ?>
							<?php foreach ( SEIWP_Tools::get_sites( array( 'number' => apply_filters( 'seiwp_sites_limit', 100 ) ) ) as $blog ) : ?>
							<tr>
																		<td class="seiwp-settings-title-s">
																			<label for="network_tableid"><?php echo '<strong>'.$blog['domain'].$blog['path'].'</strong>: ';?></label>
																		</td>
																		<td>
																			<select id="network_tableid" <?php disabled(!empty($options['sites_list']),false);?> name="options[network_tableid][<?php echo $blog['blog_id'];?>]">
									<?php if ( ! empty( $options['sites_list'] ) ) : ?>
										<?php foreach ( $options['sites_list'] as $items ) : ?>
											<?php if ( $items[0] ) : ?>
												<?php $temp_id = $blog['blog_id']; ?>
												<option <?php disabled($items[1],'siteUnverifiedUser') ?> value="<?php echo esc_attr( $items[0] );?>" <?php selected( $items[0], isset( $options['network_tableid']->$temp_id ) ? $options['network_tableid']->$temp_id : '');?> title="<?php echo __( "Property Name:", 'search-engine-insights' ) . ' ' . esc_attr( $items[0] );?>">
													 <?php echo esc_html( rtrim( $items[0],'/' ) . ' &#8658; ' . $items[1] );?>
												</option>
											<?php endif; ?>
										<?php endforeach; ?>
									<?php else : ?>
												<option value="">
													<?php _e( "Property not found", 'search-engine-insights' );?>
												</option>
									<?php endif; ?>
									</select>
																			<br />
																		</td>
																	</tr>
							<?php endforeach; ?>
																	<tr>
																		<td colspan="2">
																			<hr>
																		</td>
																	</tr>
																	<tr>
																		<td colspan="2" class="submit">
																			<input type="submit" name="Submit" class="button button-primary" value="<?php _e('Save Changes', 'search-engine-insights' ) ?>" />
																		</td>
																	</tr>
							<?php else : ?>
							<tr>
																		<td colspan="2">
																			<hr>
																		</td>
																	</tr>
																	<tr>
																		<td colspan="2">
																		 <?php $auth = $seiwp->gapi_controller->client->createAuthUrl();?>
																			<button type="submit" class="button button-secondary" formaction="<?php echo esc_url_raw( $auth ); ?>"><?php _e( "Authorize Plugin", 'search-engine-insights' ); ?></button>
																			<button type="submit" name="Clear" class="button button-secondary"><?php _e( "Clear Cache", 'search-engine-insights' ); ?></button>
																		</td>
																	</tr>
							<?php endif; ?>
							<tr>
																		<td colspan="2">
																			<hr>
																		</td>
																	</tr>
																</table>
															</form>
		<?php SEIWP_Tools::load_view( 'admin/views/settings-sidebar.php', array() ); ?>
				<?php return; ?>
			<?php endif;?>
						</table>
					</form>
		<?php

		SEIWP_Tools::load_view( 'admin/views/settings-sidebar.php', array() );
	}
}
