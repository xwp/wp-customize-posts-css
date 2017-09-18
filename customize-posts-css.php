<?php
/**
 * Plugin Name: Customize Posts CSS
 * Version: 0.1.0
 * Description: Define styles for your posts and preview in the Customizer.
 * Plugin URI: https://github.com/xwp/wp-customize-posts-css
 * Author: Weston Ruter, XWP
 * Author URI: https://make.xwp.co/
 *
 * Copyright (c) 2017 XWP (https://make.xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package Customize_Posts_CSS
 */

global $customize_posts_css_plugin;

if ( version_compare( phpversion(), '5.4', '<' ) || ! file_exists( ABSPATH . WPINC . '/customize/class-wp-customize-code-editor-control.php' ) ) {
	if ( defined( 'WP_CLI' ) ) {
		WP_CLI::warning( _customize_post_css_php_version_text() );
	} else {
		add_action( 'admin_notices', '_customize_posts_css_dependency_error' );
	}
} else {
	require_once __DIR__ . '/php/class-plugin.php';
	$class = 'Customize_Posts_CSS\\Plugin';
	$customize_posts_css_plugin = new $class();
	add_action( 'plugins_loaded', array( $customize_posts_css_plugin, 'init' ) );
}

/**
 * Admin notice for incompatible versions of PHP.
 */
function _customize_posts_css_dependency_error() {
	printf( '<div class="error"><p>%s</p></div>', esc_html( _customize_post_css_php_version_text() ) );
}

/**
 * String describing the minimum PHP version.
 *
 * @return string
 */
function _customize_post_css_php_version_text() {
	return __( 'Customize Posts CSS plugin error: Either your version of PHP is too old to run this plugin or you are not running on the sufficiently-patched version of WordPress 4.9-alpha.', 'customize-posts-css' );
}
