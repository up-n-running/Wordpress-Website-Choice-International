<?php
/*
Plugin Name: up-n-running Core Tweaks
Version: 1.0
Plugin URI: http://www.upnrunning.co.uk
Description: Simple additions, mostly to help lockdown security
Author: John Milner
Author URI: http://www.upnrunning.co.uk
Text Domain: upnrunning
*/

/*
Copyright (c) 2020, John Milner

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* CONSTANTS DEFINITIONS */
//Version Number
define( 'UNR_CORE_PLUGIN_VERSION', 1.000 ); //self expanatory
define( 'UNR_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'UNR_CORE_URL',  plugin_dir_url( __FILE__ ) );

class UnrCorePlugin {
    
    public function __construct() {
        //on initialisation remove the wordpress version number
        //from the header meta data to make zero day attacks harder
        //for script kiddies
        add_filter('the_generator', array( $this, 'remove_version_from_page_meta' ));
    }

    public function remove_version_from_page_meta() {
        return '';
    }
    
    
}
new UnrCorePlugin();