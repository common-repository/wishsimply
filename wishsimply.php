<?php
/**
 * Plugin Name: WishSimply
 * Description: This plugin allows users to embed wishlists from https://wishsimply.com/.
 * Author: WishSimply
 * Author URI: https://wishsimply.com/
 * Version: 1.0.3
 * License: GPLv3
 * Text Domain: wishsimply
 * Domain Path: /languages
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'plugins_loaded', 'wishsimply_plugin_init' );

function wishsimply_plugin_init() {

    load_plugin_textdomain( 'wishsimply', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

    if ( ! class_exists( 'WP_Wishsimply' ) ) :

        class WP_Wishsimply {
            /**
             * @var Const Plugin Version Number
             */
            const VERSION = '1.0.0';

            /**
             * @var Singleton The reference the *Singleton* instance of this class
             */
            private static $instance;

            /**
             * Returns the *Singleton* instance of this class.
             *
             * @return Singleton The *Singleton* instance.
             */
            public static function get_instance() {
                if ( null === self::$instance ) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            private function __clone() {}

            private function __wakeup() {}

            /**
             * Protected constructor to prevent creating a new instance of the
             * *Singleton* via the `new` operator from outside of this class.
             */
            private function __construct() {

                define('WP_WISHSIMPLY_BASE_FILE', plugin_basename(__FILE__));
                define('WP_WISHSIMPLY_BASE_URL', trailingslashit( plugins_url( 'wishsimply' ) ) );
                define('WP_WISHSIMPLY_PATH', plugin_dir_path(__FILE__));
                define('WP_WISHSIMPLY_VERSION', self::VERSION);

                add_action( 'admin_init', array( $this, 'install' ) );
                $this->init();
            }

            /**
             * Init function.
             *
             * @since 1.0.0
             */
            public function init() {
                require_once( dirname( __FILE__ ) . '/includes/class-wishsimply.php' );
                $wishsimply = new Wishsimply();
                $wishsimply->init();
            }

            /**
             * Updates the plugin version in db
             *
             * @since 1.0.0
             */
            public function update_plugin_version() {
                delete_option( 'wishsimply_version' );
                update_option( 'wishsimply_version', WP_WISHSIMPLY_VERSION );
            }

            /**
             * Handles upgrade routines.
             *
             * @since 1.0.0
             */
            public function install() {
                if ( ! is_plugin_active( WP_WISHSIMPLY_BASE_FILE ) ) {
                    return;
                }

                if ( ( WP_WISHSIMPLY_VERSION !== get_option( 'wishsimply_version' ) ) ) {

                    $this->update_plugin_version();
                }
            }

        }

        WP_Wishsimply::get_instance();
    endif;
}
