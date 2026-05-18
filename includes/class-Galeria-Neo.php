<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Galeria_Neo {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->check_elementor();
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_elementor_hooks();
    }

    private function define_constants(): void {
        define( 'GALERIA_NEO_VERSION',      '1.0.3' );
        define( 'GALERIA_NEO_PLUGIN_DIR',   plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'GALERIA_NEO_PLUGIN_URL',   plugin_dir_url( dirname( __FILE__ ) ) );
        define( 'GALERIA_NEO_GITHUB_OWNER', 'tikovolpe' );
        define( 'GALERIA_NEO_GITHUB_REPO',  'plugin-galeria-neo' );
        define( 'GALERIA_NEO_PLUGIN_FILE',  'galeria-neo/galeria-neo.php' );
        define( 'GALERIA_NEO_TRANSIENT',    'galeria_neo_update_check' );
    }

    private function check_elementor(): void {
        add_action( 'admin_notices', function (): void {
            if ( did_action( 'elementor/loaded' ) ) {
                return;
            }
            $message = sprintf(
                '<strong>Galeria Neo</strong> requer o Elementor instalado e ativo.'
            );
            printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $message ) );
        } );
    }

    private function define_admin_hooks(): void {
        require_once GALERIA_NEO_PLUGIN_DIR . 'includes/class-Galeria-Neo-Updater.php';
        Galeria_Neo_Updater::get_instance();

        if ( is_admin() ) {
            require_once GALERIA_NEO_PLUGIN_DIR . 'admin/class-Galeria-Neo-Admin.php';
            $admin = new Galeria_Neo_Admin();
            add_filter( 'plugin_action_links_galeria-neo/galeria-neo.php', [ $admin, 'add_action_links' ] );
            add_action( 'admin_init',    [ $admin, 'handle_manual_check' ] );
            add_action( 'admin_notices', [ $admin, 'show_update_notice' ] );
        }
    }

    private function load_dependencies(): void {
        require_once GALERIA_NEO_PLUGIN_DIR . 'includes/helpers.php';
        require_once GALERIA_NEO_PLUGIN_DIR . 'includes/class-Galeria-Neo-CPT.php';
        require_once GALERIA_NEO_PLUGIN_DIR . 'public/class-Galeria-Neo-Public.php';
        new Galeria_Neo_CPT();

        if ( is_admin() ) {
            require_once GALERIA_NEO_PLUGIN_DIR . 'admin/class-Galeria-Neo-Import.php';
            new Galeria_Neo_Import();
        }
    }

    private function define_public_hooks(): void {
        $public = new Galeria_Neo_Public();
        add_action( 'wp_enqueue_scripts', [ $public, 'register_assets' ] );
    }

    private function define_elementor_hooks(): void {
        add_action( 'elementor/widgets/register', function ( \Elementor\Widgets_Manager $manager ): void {
            if ( ! did_action( 'elementor/loaded' ) ) {
                return;
            }
            require_once GALERIA_NEO_PLUGIN_DIR . 'widgets/class-widget-Galeria-Neo.php';
            $manager->register( new Widget_Galeria_Neo() );
        } );
    }
}
