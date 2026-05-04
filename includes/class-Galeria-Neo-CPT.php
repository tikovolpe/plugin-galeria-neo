<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Galeria_Neo_CPT {
    public const POST_TYPE = 'galeria_neo_logo';
    public const TAXONOMY  = 'categoria-logo';

    public function __construct() {
        add_action( 'init',                     [ $this, 'register_cpt' ] );
        add_action( 'init',                     [ $this, 'register_taxonomy' ] );
        add_action( 'add_meta_boxes',           [ $this, 'add_meta_box' ] );
        add_action( 'save_post',                [ $this, 'save_meta' ] );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns',       [ $this, 'add_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'fill_columns' ], 10, 2 );
        add_action( 'restrict_manage_posts',    [ $this, 'taxonomy_filter_dropdown' ] );
        add_action( 'pre_get_posts',            [ $this, 'apply_taxonomy_filter' ] );
        add_action( 'quick_edit_custom_box',    [ $this, 'quick_edit_field' ], 10, 2 );
        add_action( 'bulk_edit_custom_box',     [ $this, 'bulk_edit_field' ], 10, 2 );
        add_action( 'admin_footer',             [ $this, 'quick_edit_script' ] );
        add_action( 'wp_ajax_galeria_neo_get_common_terms', [ $this, 'ajax_get_common_terms' ] );
    }

    public function register_cpt(): void {
        $labels = [
            'name'               => 'Logos Associados e Parceiros',
            'singular_name'      => 'Logo',
            'add_new'            => 'Adicionar Logo',
            'add_new_item'       => 'Adicionar Novo Logo',
            'edit_item'          => 'Editar Logo',
            'new_item'           => 'Novo Logo',
            'view_item'          => 'Ver Logo',
            'search_items'       => 'Buscar Logos',
            'not_found'          => 'Nenhum logo encontrado',
            'not_found_in_trash' => 'Nenhum logo na lixeira',
            'all_items'          => 'Todos os Logos',
            'menu_name'          => 'Associados e Parceiros',
        ];

        register_post_type( self::POST_TYPE, [
            'labels'      => $labels,
            'public'      => false,
            'show_ui'     => true,
            'show_in_menu'=> true,
            'menu_icon'   => 'dashicons-images-alt2',
            'supports'    => [ 'title', 'thumbnail' ],
            'rewrite'     => [ 'slug' => 'logos-associados-parceiros' ],
            'show_in_rest'=> false,
        ] );
    }

    public function register_taxonomy(): void {
        $labels = [
            'name'              => 'Categorias de Logo',
            'singular_name'     => 'Categoria de Logo',
            'search_items'      => 'Buscar Categorias',
            'all_items'         => 'Todas as Categorias',
            'parent_item'       => 'Categoria Pai',
            'parent_item_colon' => 'Categoria Pai:',
            'edit_item'         => 'Editar Categoria',
            'update_item'       => 'Atualizar Categoria',
            'add_new_item'      => 'Adicionar Nova Categoria',
            'new_item_name'     => 'Nome da Nova Categoria',
            'menu_name'         => 'Categorias',
        ];

        register_taxonomy( self::TAXONOMY, self::POST_TYPE, [
            'labels'       => $labels,
            'hierarchical' => true,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'rewrite'      => [ 'slug' => 'categoria-logo' ],
            'show_in_rest' => false,
        ] );
    }

    public function add_meta_box(): void {
        add_meta_box(
            'galeria_neo_logo_url',
            'Link do Parceiro/Associado',
            [ $this, 'render_meta_box' ],
            self::POST_TYPE,
            'normal',
            'default'
        );
    }

    public function render_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'galeria_neo_save_logo_url', 'galeria_neo_logo_url_nonce' );
        ?>
        <p>
            <label for="galeria_neo_logo_url_field">URL do site do parceiro/associado:</label><br>
            <input
                type="url"
                id="galeria_neo_logo_url_field"
                name="_logo_url"
                value="<?php echo esc_attr( get_post_meta( $post->ID, '_logo_url', true ) ); ?>"
                style="width:100%"
                placeholder="https://exemplo.com.br"
            >
        </p>
        <?php
    }

    public function save_meta( int $post_id ): void {
        // Quick edit passes its own nonce; meta box uses a different one.
        $is_quick_edit = isset( $_POST['_inline_edit'] );
        $nonce_value   = $is_quick_edit
            ? ( $_POST['galeria_neo_quick_edit_nonce'] ?? '' )
            : ( $_POST['galeria_neo_logo_url_nonce']   ?? '' );
        $nonce_action  = $is_quick_edit ? 'galeria_neo_quick_edit' : 'galeria_neo_save_logo_url';

        if ( ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( get_post_type( $post_id ) !== self::POST_TYPE ) {
            return;
        }

        if ( isset( $_POST['_logo_url'] ) ) {
            $url = sanitize_url( wp_unslash( $_POST['_logo_url'] ) );
            if ( $url ) {
                update_post_meta( $post_id, '_logo_url', $url );
            } else {
                delete_post_meta( $post_id, '_logo_url' );
            }
        }
    }

    public function add_columns( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            if ( 'title' === $key ) {
                $new['thumbnail'] = 'Imagem';
            }
            $new[ $key ] = $label;
        }
        $new[ self::TAXONOMY ] = 'Categoria';
        $new['logo_link']      = 'Link';
        unset( $new['date'] );
        $new['date'] = $columns['date'] ?? 'Data';
        return $new;
    }

    public function fill_columns( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'thumbnail':
                echo get_the_post_thumbnail( $post_id, [ 60, 60 ] );
                break;

            case self::TAXONOMY:
                $terms = get_the_terms( $post_id, self::TAXONOMY );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $names = array_map( static fn( $t ) => esc_html( $t->name ), $terms );
                    echo implode( ', ', $names );
                    $ids = array_map( static fn( $t ) => (int) $t->term_id, $terms );
                } else {
                    echo '—';
                    $ids = [];
                }
                echo '<span class="gn-term-ids" style="display:none">' . esc_attr( wp_json_encode( $ids ) ) . '</span>';
                break;

            case 'logo_link':
                $url = get_post_meta( $post_id, '_logo_url', true );
                if ( $url ) {
                    printf(
                        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                        esc_url( $url ),
                        esc_html( $url )
                    );
                } else {
                    echo '—';
                }
                break;
        }
    }

    private function get_filter_key(): string {
        // PHP normalizes hyphens to underscores in $_GET keys.
        return str_replace( '-', '_', self::TAXONOMY );
    }

    public function taxonomy_filter_dropdown(): void {
        global $typenow;
        if ( self::POST_TYPE !== $typenow ) {
            return;
        }

        $current = $_GET[ $this->get_filter_key() ] ?? '';
        $terms   = get_terms( [ 'taxonomy' => self::TAXONOMY, 'hide_empty' => false ] );

        echo '<select name="' . esc_attr( $this->get_filter_key() ) . '">';
        echo '<option value="">' . esc_html__( 'Todas as categorias' ) . '</option>';
        echo '<option value="-1"' . selected( $current, '-1', false ) . '>— Sem categoria —</option>';

        if ( is_array( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                printf(
                    '<option value="%d"%s>%s</option>',
                    (int) $term->term_id,
                    selected( (int) $current, (int) $term->term_id, false ),
                    esc_html( $term->name )
                );
            }
        }

        echo '</select>';
    }

    public function apply_taxonomy_filter( \WP_Query $query ): void {
        global $pagenow;

        $filter_key = $this->get_filter_key();

        if (
            ! is_admin() ||
            'edit.php' !== $pagenow ||
            ( $query->query_vars['post_type'] ?? '' ) !== self::POST_TYPE ||
            ! isset( $_GET[ $filter_key ] ) ||
            '' === $_GET[ $filter_key ]
        ) {
            return;
        }

        $term_id = (int) $_GET[ $filter_key ];

        if ( -1 === $term_id ) {
            $query->set( 'tax_query', [ [
                'taxonomy' => self::TAXONOMY,
                'operator' => 'NOT EXISTS',
            ] ] );
        } elseif ( $term_id > 0 ) {
            $query->set( 'tax_query', [ [
                'taxonomy' => self::TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ] );
        }
    }

    public function bulk_edit_field( string $column_name, string $post_type ): void {
        if ( 'logo_link' !== $column_name || self::POST_TYPE !== $post_type ) {
            return;
        }
        wp_nonce_field( 'galeria_neo_common_terms', 'galeria_neo_common_terms_nonce' );
    }

    public function ajax_get_common_terms(): void {
        check_ajax_referer( 'galeria_neo_common_terms', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

        $raw_ids  = isset( $_POST['post_ids'] ) ? (array) $_POST['post_ids'] : [];
        $post_ids = array_values( array_filter( array_map( 'absint', $raw_ids ) ) );

        if ( empty( $post_ids ) ) {
            wp_send_json_success( [] );
        }

        $first  = wp_get_post_terms( $post_ids[0], self::TAXONOMY, [ 'fields' => 'ids' ] );
        $common = is_wp_error( $first ) ? [] : $first;

        foreach ( array_slice( $post_ids, 1 ) as $pid ) {
            if ( empty( $common ) ) break;
            $terms  = wp_get_post_terms( $pid, self::TAXONOMY, [ 'fields' => 'ids' ] );
            $common = is_wp_error( $terms ) ? [] : array_values( array_intersect( $common, $terms ) );
        }

        wp_send_json_success( array_values( $common ) );
    }

    public function quick_edit_field( string $column_name, string $post_type ): void {
        if ( 'logo_link' !== $column_name || self::POST_TYPE !== $post_type ) {
            return;
        }
        wp_nonce_field( 'galeria_neo_quick_edit', 'galeria_neo_quick_edit_nonce' );
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title">Link do Parceiro</span>
                    <input type="url" name="_logo_url" id="galeria_neo_qe_logo_url" value="" placeholder="https://...">
                </label>
            </div>
        </fieldset>
        <?php
    }

    public function quick_edit_script(): void {
        $screen = get_current_screen();
        if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
            return;
        }
        ?>
        <script>
        (function($){
            var taxonomy   = 'categoria-logo';
            var nonce      = <?php echo wp_json_encode( wp_create_nonce( 'galeria_neo_common_terms' ) ); ?>;

            // ── Quick edit: pre-fill URL + pre-check categories ───────────
            var _inlineEditPost = inlineEditPost.edit;
            inlineEditPost.edit = function( id ) {
                _inlineEditPost.apply( this, arguments );
                var postId   = typeof id === 'object' ? parseInt( this.getId( id ) ) : id;
                var $row     = $( '#post-' + postId );
                var $editRow = $( '#edit-' + postId );

                // URL
                var url = $row.find( '.column-logo_link a' ).attr( 'href' ) || '';
                $editRow.find( '#galeria_neo_qe_logo_url' ).val( url );

                // Categories
                var raw     = $row.find( '.gn-term-ids' ).text();
                var termIds = raw ? JSON.parse( raw ) : [];
                $editRow.find( 'input[name="tax_input[' + taxonomy + '][]"]' ).each( function() {
                    $( this ).prop( 'checked', termIds.indexOf( parseInt( $( this ).val(), 10 ) ) !== -1 );
                } );
            };

            // ── Bulk edit: pre-check common categories via AJAX ───────────
            $( '#doaction, #doaction2' ).on( 'click', function() {
                var $select = $( this ).siblings( 'select[name="action"], select[name="action2"]' );
                if ( $select.val() !== 'edit' ) return;

                var postIds = [];
                $( 'input[name="post[]"]:checked' ).each( function() {
                    postIds.push( parseInt( $( this ).val(), 10 ) );
                } );
                if ( ! postIds.length ) return;

                $.post( ajaxurl, {
                    action:   'galeria_neo_get_common_terms',
                    post_ids: postIds,
                    nonce:    nonce,
                }, function( response ) {
                    if ( ! response.success ) return;
                    var common = response.data;
                    setTimeout( function() {
                        $( '#bulk-edit input[name="tax_input[' + taxonomy + '][]"]' ).each( function() {
                            $( this ).prop( 'checked', common.indexOf( parseInt( $( this ).val(), 10 ) ) !== -1 );
                        } );
                    }, 150 );
                } );
            } );
        }(jQuery));
        </script>
        <?php
    }
}
