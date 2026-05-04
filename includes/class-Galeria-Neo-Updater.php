<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Galeria_Neo_Updater {

    private static ?self $instance = null;
    private ?object $release_data  = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'inject_update' ] );
        add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );
        add_filter( 'upgrader_source_selection',             [ $this, 'fix_folder_name' ], 10, 4 );
        add_action( 'upgrader_process_complete',             [ $this, 'clear_cache' ], 10, 2 );
    }

    // ── GitHub API ────────────────────────────────────────────────────

    private function check_github(): ?object {
        $cached = get_transient( GALERIA_NEO_TRANSIENT );
        if ( false !== $cached ) {
            $this->release_data = $cached;
            return $cached;
        }

        $api_url  = 'https://api.github.com/repos/' . GALERIA_NEO_GITHUB_OWNER . '/' . GALERIA_NEO_GITHUB_REPO . '/releases/latest';
        $response = wp_remote_get( $api_url, [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
            ],
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        if ( empty( $body->tag_name ) ) {
            return null;
        }

        $data = (object) [
            'version'      => ltrim( $body->tag_name, 'vV' ),
            'download_url' => $body->zipball_url ?? '',
            'changelog'    => $body->body ?? '',
            'html_url'     => $body->html_url ?? '',
        ];

        set_transient( GALERIA_NEO_TRANSIENT, $data, 12 * HOUR_IN_SECONDS );
        $this->release_data = $data;

        return $data;
    }

    // ── WordPress update transient injection ──────────────────────────

    public function inject_update( object $transient ): object {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->check_github();
        if ( ! $release ) {
            return $transient;
        }

        if ( version_compare( $release->version, GALERIA_NEO_VERSION, '>' ) ) {
            $transient->response[ GALERIA_NEO_PLUGIN_FILE ] = (object) [
                'slug'        => 'galeria-neo',
                'plugin'      => GALERIA_NEO_PLUGIN_FILE,
                'new_version' => $release->version,
                'url'         => $release->html_url,
                'package'     => $release->download_url,
                'tested'      => get_bloginfo( 'version' ),
                'requires'    => '6.0',
                'requires_php' => '8.0',
            ];
        }

        return $transient;
    }

    // ── Plugin info popup ("Ver detalhes") ────────────────────────────

    public function plugin_info( mixed $result, string $action, object $args ): mixed {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }
        if ( empty( $args->slug ) || 'galeria-neo' !== $args->slug ) {
            return $result;
        }

        $release = $this->check_github();
        if ( ! $release ) {
            return $result;
        }

        return (object) [
            'name'          => 'Galeria Neo',
            'slug'          => 'galeria-neo',
            'version'       => $release->version,
            'author'        => 'LVBA Comunicação',
            'homepage'      => $release->html_url,
            'requires'      => '6.0',
            'requires_php'  => '8.0',
            'tested'        => get_bloginfo( 'version' ),
            'download_link' => $release->download_url,
            'sections'      => [
                'changelog' => nl2br( esc_html( $release->changelog ) ) ?: 'Veja as releases no GitHub.',
            ],
        ];
    }

    // ── Fix folder name after GitHub zip extraction ───────────────────

    public function fix_folder_name( string $source, string $remote_source, object $upgrader, array $hook_extra ): string {
        global $wp_filesystem;

        if ( empty( $hook_extra['plugin'] ) || GALERIA_NEO_PLUGIN_FILE !== $hook_extra['plugin'] ) {
            return $source;
        }

        $correct_folder = trailingslashit( $remote_source ) . 'galeria-neo/';

        if ( $source !== $correct_folder ) {
            if ( $wp_filesystem->exists( $correct_folder ) ) {
                $wp_filesystem->delete( $correct_folder, true );
            }
            $wp_filesystem->move( $source, $correct_folder );
            return $correct_folder;
        }

        return $source;
    }

    // ── Cache management ──────────────────────────────────────────────

    public function clear_cache( object $upgrader, array $hook_extra ): void {
        if (
            ! empty( $hook_extra['action'] ) && 'update' === $hook_extra['action'] &&
            ! empty( $hook_extra['type'] )   && 'plugin' === $hook_extra['type']   &&
            ! empty( $hook_extra['plugins'] ) &&
            in_array( GALERIA_NEO_PLUGIN_FILE, $hook_extra['plugins'], true )
        ) {
            delete_transient( GALERIA_NEO_TRANSIENT );
        }
    }

    public function force_check(): ?object {
        delete_transient( GALERIA_NEO_TRANSIENT );
        $this->release_data = null;
        return $this->check_github();
    }

    public function get_release_data(): ?object {
        return $this->release_data;
    }
}
