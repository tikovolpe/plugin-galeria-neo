<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Galeria_Neo_Public {

    public function register_assets(): void {
        wp_register_style(
            'galeria-neo-public',
            GALERIA_NEO_PLUGIN_URL . 'public/css/galeria-neo-public.css',
            [],
            GALERIA_NEO_VERSION
        );

        wp_register_script(
            'galeria-neo-public',
            GALERIA_NEO_PLUGIN_URL . 'public/js/galeria-neo-public.js',
            [],
            GALERIA_NEO_VERSION,
            true
        );
    }
}
