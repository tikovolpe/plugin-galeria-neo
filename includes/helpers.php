<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Ordena itens do REPEATER gallery pelo campo de attachment.
 *
 * @param array  $items      Itens do repeater (cada item tem ['image']['id']).
 * @param string $order_by   'title' | 'date'
 * @param string $direction  'asc' | 'desc'
 * @return array
 */
function galeria_neo_sort_items( array $items, string $order_by, string $direction = 'asc' ): array {
    if ( empty( $items ) ) {
        return $items;
    }

    usort( $items, function ( array $a, array $b ) use ( $order_by ): int {
        $id_a = absint( $a['image']['id'] ?? 0 );
        $id_b = absint( $b['image']['id'] ?? 0 );

        switch ( $order_by ) {
            case 'date':
                $val_a = $id_a ? (string) get_post_field( 'post_date', $id_a ) : '';
                $val_b = $id_b ? (string) get_post_field( 'post_date', $id_b ) : '';
                return strcmp( $val_a, $val_b );

            case 'title':
            default:
                $val_a = $id_a ? get_the_title( $id_a ) : '';
                $val_b = $id_b ? get_the_title( $id_b ) : '';
                return strcmp( $val_a, $val_b );
        }
    } );

    if ( 'desc' === $direction ) {
        $items = array_reverse( $items );
    }

    return $items;
}
