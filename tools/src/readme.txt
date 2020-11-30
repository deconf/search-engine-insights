/**
 * Author: Alin Marcu
 * Author URI: https://deconf.com
 * Copyright 2013 Alin Marcu
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
 
 * Custom SEIWP EndPoint: 

 		- added an action hook to IO -> Abstract -> MakeRequest to enable custom endpoint support:
 
			   public function makeRequest(SEI_Http_Request $request)
			   {
			
				  	// Add support for SEIWP Endpoint
				  	do_action('seiwp_endpoint_support', $request);
				  	
				  	...
				 
				 }
 
 * Changed 'Google' provider to 'SEI', to avoid conflicts with different versions of GAPI PHP Client, due to PHP 5.3 and lower versions autoloading limitations
 
 * Renamed 'google_api_php_client_autoload' to 'google_api_php_client_autoload_seiwp', due to PHP 5.3 and lower versions autoloading limitations and inability to use anonymous functions 	