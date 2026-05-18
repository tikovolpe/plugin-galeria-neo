<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Galeria_Neo_Admin {

    // ── Plugin action links ───────────────────────────────────────────

    public function add_action_links( array $links ): array {
        $nonce = wp_create_nonce( 'galeria_neo_check_update' );
        $url   = admin_url( 'plugins.php?galeria_neo_check_update=1&_wpnonce=' . $nonce );

        $check_link = '<a href="' . esc_url( $url ) . '">Verificar atualização</a>';

        array_unshift( $links, $check_link );

        return $links;
    }

    // ── Manual update check handler ───────────────────────────────────

    public function handle_manual_check(): void {
        if ( empty( $_GET['galeria_neo_check_update'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'galeria_neo_check_update' ) ) {
            wp_die( 'Nonce inválido.' );
        }

        if ( ! current_user_can( 'manage_plugins' ) ) {
            wp_die( 'Permissão negada.' );
        }

        $release = Galeria_Neo_Updater::get_instance()->force_check();

        $has_update = $release && version_compare( $release->version, GALERIA_NEO_VERSION, '>' );

        $redirect = admin_url( 'plugins.php?galeria_neo_checked=1&has_update=' . ( $has_update ? '1' : '0' ) );

        if ( $has_update ) {
            $redirect = add_query_arg( 'new_version', rawurlencode( $release->version ), $redirect );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    // ── Admin notice after manual check ──────────────────────────────

    public function show_update_notice(): void {
        if ( empty( $_GET['galeria_neo_checked'] ) ) {
            return;
        }

        $has_update  = ! empty( $_GET['has_update'] ) && '1' === $_GET['has_update'];
        $new_version = sanitize_text_field( wp_unslash( $_GET['new_version'] ?? '' ) );

        if ( $has_update && $new_version ) {
            $type    = 'notice-warning';
            $message = sprintf(
                'Galeria Neo: nova versão <strong>%s</strong> disponível. <a href="%s">Atualizar agora</a>.',
                esc_html( $new_version ),
                esc_url( admin_url( 'plugins.php' ) )
            );
        } else {
            $type    = 'notice-success';
            $message = 'Galeria Neo: plugin está na versão mais recente (' . esc_html( GALERIA_NEO_VERSION ) . ').';
        }

        printf(
            '<div class="notice %s is-dismissible"><p>%s</p></div>',
            esc_attr( $type ),
            wp_kses_post( $message )
        );
    }
}
