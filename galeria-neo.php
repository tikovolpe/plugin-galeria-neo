<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Plugin Name:       Galeria Neo
 * Plugin URI:        https://github.com/tikovolpe/plugin-galeria-neo
 * Description:       Widget Elementor de galeria de imagens com grid e carrossel responsivos, ordenação e efeitos hover.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            LVBA Comunicação
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       galeria-neo
 * Domain Path:       /languages
 */

require_once plugin_dir_path( __FILE__ ) . 'includes/class-Galeria-Neo.php';

add_action( 'plugins_loaded', function (): void {
    Galeria_Neo::get_instance();
} );
