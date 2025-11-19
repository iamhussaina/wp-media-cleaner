<?php
/**
 * Bootstrapper for the Hussainas Media Cleaner library.
 * This file ensures the class is loaded and instantiated safely.
 *
 * @package Hussainas_Media_Cleaner
 * @version     1.0.0
 * @author      Hussain Ahmed Shrabon
 * @license     GPL-2.0-or-later
 * @link        https://github.com/iamhussaina
 * @textdomain  hussainas
 */

// Prevent direct access to the file for security.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define the path to the class directory.
define( 'HUSSAINAS_CLEANER_PATH', plugin_dir_path( __FILE__ ) );

// Include the main engine class.
require_once HUSSAINAS_CLEANER_PATH . 'classes/class-hussainas-cleaner-engine.php';

/**
 * Initialize the cleaner instance.
 * This ensures the admin menu and logic are hooked into WordPress.
 */
function hussainas_start_cleaner() {
    if ( class_exists( 'Hussainas_Cleaner_Engine' ) ) {
        new Hussainas_Cleaner_Engine();
    }
}

// Hook into 'after_setup_theme' to ensure theme resources are ready before loading.
add_action( 'after_setup_theme', 'hussainas_start_cleaner' );
