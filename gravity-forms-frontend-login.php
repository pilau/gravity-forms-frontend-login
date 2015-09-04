<?php

/*
Plugin Name: Gravity Forms Frontend Login
Plugin URI: https://github.com/pilau/gravity-forms-frontend-login
Description: A Gravity Forms add-on to provide frontend login functionality.
Version: 1.1.0
Author: Steve Taylor
Author URI: http://sltaylor.co.uk

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

add_action( 'gform_loaded', array( 'GFFrontendLogin_Bootstrap', 'load' ), 5 );
class GFFrontendLogin_Bootstrap {
	public static function load(){
		require_once( 'class-gravity-forms-frontend-login.php' );
		GFAddOn::register( 'GFFrontendLogin' );
	}
}
