<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Galeria_Neo_Import {
    private const GROUPS = [
        'associados' => [
            'label'    => 'Associados',
            'term'     => 'Associados',
            'slug'     => 'associados',
            'patterns' => [ 'associad' ],
        ],
        'parceiros' => [
            'label'    => 'Parceiros',
            'term'     => 'Parceiros',
            'slug'     => 'parceiros',
            'patterns' => [ 'parceir', 'parceria' ],
        ],
    ];

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            'edit.php?post_type=' . Galeria_Neo_CPT::POST_TYPE,
            'Importar Logos',
            'Importar Logos',
            'manage_options',
            'galeria-neo-import',
            [ $this, 'render_page' ]
        );
    }

    public function render_page(): void {
        $result          = null;
        $selected_groups = [ 'parceiros' ];
        $sync_existing   = true;

        if (
            isset( $_POST['galeria_neo_run_import'] ) &&
            check_admin_referer( 'galeria_neo_import', 'galeria_neo_import_nonce' )
        ) {
            $selected_groups = $this->sanitize_groups( $_POST['galeria_neo_import_groups'] ?? [] );
            $sync_existing   = ! empty( $_POST['galeria_neo_sync_existing'] );
            $result          = $this->run_import( $selected_groups, $sync_existing );
        }
        ?>
        <div class="wrap">
            <h1>Importar Logos</h1>
            <p>
                Busca imagens da biblioteca de mídia pelo título, cria posts no CPT
                <em>Logos Associados e Parceiros</em> e vincula automaticamente às categorias.
            </p>

            <?php if ( null !== $result ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        Importação concluída:
                        <strong><?php echo absint( $result['created'] ); ?></strong> post(s) criado(s),
                        <strong><?php echo absint( $result['skipped'] ); ?></strong> já existia(m),
                        <strong><?php echo absint( $result['categorized'] ); ?></strong> post(s) existente(s) categorizado(s)/atualizado(s).
                    </p>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field( 'galeria_neo_import', 'galeria_neo_import_nonce' ); ?>
                <fieldset>
                    <legend class="screen-reader-text">Tipos de logo para importar</legend>
                    <?php foreach ( self::GROUPS as $key => $group ) : ?>
                        <label style="display:block;margin:0 0 8px;">
                            <input
                                type="checkbox"
                                name="galeria_neo_import_groups[]"
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php checked( in_array( $key, $selected_groups, true ) ); ?>
                            >
                            <?php echo esc_html( $group['label'] ); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <p class="description">
                    Parceiros busca por "parceiro", "parceiros", "parceria" e "parcerias".
                    Associados busca por "associado", "associada", "associados" e "associadas".
                </p>

                <p>
                    <label>
                        <input
                            type="checkbox"
                            name="galeria_neo_sync_existing"
                            value="1"
                            <?php checked( $sync_existing ); ?>
                        >
                        Atualizar categorias dos posts já existentes pelo título
                    </label>
                </p>

                <p>
                    <input
                        type="submit"
                        name="galeria_neo_run_import"
                        class="button button-primary"
                        value="Executar Importação"
                    >
                </p>
            </form>
        </div>
        <?php
    }

    private function sanitize_groups( $groups ): array {
        $groups = array_map( 'sanitize_key', (array) wp_unslash( $groups ) );
        $groups = array_values( array_intersect( $groups, array_keys( self::GROUPS ) ) );

        return ! empty( $groups ) ? $groups : [ 'parceiros' ];
    }

    private function run_import( array $groups, bool $sync_existing ): array {
        $groups = $this->sanitize_groups( $groups );
        $result = [
            'created'     => 0,
            'skipped'     => 0,
            'categorized' => 0,
        ];

        if ( $sync_existing ) {
            $result['categorized'] += $this->sync_existing_posts();
        }

        add_filter( 'posts_where', [ $this, 'filter_title_like' ], 10, 2 );

        $query = new WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'galeria_neo_title_patterns' => $this->get_patterns( $groups ),
        ] );

        remove_filter( 'posts_where', [ $this, 'filter_title_like' ], 10 );

        foreach ( $query->posts as $attachment ) {
            $title          = $attachment->post_title;
            $matched_groups = $this->detect_groups( $title, $groups );

            if ( empty( $matched_groups ) ) {
                continue;
            }

            $existing = $this->find_existing_post_id( $title, (int) $attachment->ID );

            if ( $existing ) {
                if ( $this->assign_terms( $existing, $matched_groups ) ) {
                    $result['categorized']++;
                }
                $result['skipped']++;
                continue;
            }

            $post_id = wp_insert_post( [
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => Galeria_Neo_CPT::POST_TYPE,
            ] );

            if ( is_wp_error( $post_id ) || ! $post_id ) {
                continue;
            }

            set_post_thumbnail( $post_id, $attachment->ID );
            $this->assign_terms( $post_id, $matched_groups );
            $result['created']++;
        }

        return $result;
    }

    private function sync_existing_posts(): int {
        $updated = 0;

        $posts = get_posts( [
            'post_type'              => Galeria_Neo_CPT::POST_TYPE,
            'post_status'            => [ 'publish', 'draft', 'pending', 'private' ],
            'posts_per_page'         => -1,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields'                 => 'ids',
        ] );

        foreach ( $posts as $post_id ) {
            $matched_groups = $this->detect_groups( $this->get_post_search_text( (int) $post_id ), array_keys( self::GROUPS ) );
            if ( empty( $matched_groups ) ) {
                continue;
            }

            if ( $this->assign_terms( (int) $post_id, $matched_groups ) ) {
                $updated++;
            }
        }

        return $updated;
    }

    private function get_patterns( array $groups ): array {
        $patterns = [];

        foreach ( $groups as $group ) {
            $patterns = array_merge( $patterns, self::GROUPS[ $group ]['patterns'] ?? [] );
        }

        return array_values( array_unique( $patterns ) );
    }

    private function detect_groups( string $title, array $allowed_groups ): array {
        $normalized = strtolower( remove_accents( $title ) );
        $matched    = [];

        foreach ( $allowed_groups as $group ) {
            foreach ( self::GROUPS[ $group ]['patterns'] ?? [] as $pattern ) {
                if ( false !== strpos( $normalized, $pattern ) ) {
                    $matched[] = $group;
                    break;
                }
            }
        }

        return array_values( array_unique( $matched ) );
    }

    private function get_post_search_text( int $post_id ): string {
        $text         = get_the_title( $post_id );
        $thumbnail_id = get_post_thumbnail_id( $post_id );

        if ( $thumbnail_id ) {
            $text .= ' ' . get_the_title( $thumbnail_id );

            $file = get_attached_file( $thumbnail_id );
            if ( $file ) {
                $text .= ' ' . basename( $file );
            }
        }

        return $text;
    }

    private function find_existing_post_id( string $title, int $attachment_id = 0 ): int {
        $existing = get_posts( [
            'post_type'              => Galeria_Neo_CPT::POST_TYPE,
            'title'                  => $title,
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields'                 => 'ids',
        ] );

        if ( ! empty( $existing ) ) {
            return (int) $existing[0];
        }

        if ( ! $attachment_id ) {
            return 0;
        }

        $existing = get_posts( [
            'post_type'              => Galeria_Neo_CPT::POST_TYPE,
            'post_status'            => [ 'publish', 'draft', 'pending', 'private' ],
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields'                 => 'ids',
            'meta_query'             => [
                [
                    'key'   => '_thumbnail_id',
                    'value' => $attachment_id,
                ],
            ],
        ] );

        return ! empty( $existing ) ? (int) $existing[0] : 0;
    }

    private function assign_terms( int $post_id, array $groups ): bool {
        $term_ids = [];

        foreach ( $groups as $group ) {
            $term_id = $this->get_or_create_term_id( $group );
            if ( $term_id ) {
                $term_ids[] = $term_id;
            }
        }

        if ( empty( $term_ids ) ) {
            return false;
        }

        $current_ids = wp_get_object_terms( $post_id, Galeria_Neo_CPT::TAXONOMY, [ 'fields' => 'ids' ] );
        if ( is_wp_error( $current_ids ) ) {
            $current_ids = [];
        }

        $missing_ids = array_diff( $term_ids, array_map( 'intval', $current_ids ) );
        if ( empty( $missing_ids ) ) {
            return false;
        }

        $result = wp_set_object_terms( $post_id, $term_ids, Galeria_Neo_CPT::TAXONOMY, true );

        return ! is_wp_error( $result );
    }

    private function get_or_create_term_id( string $group ): int {
        if ( empty( self::GROUPS[ $group ] ) ) {
            return 0;
        }

        $group_config = self::GROUPS[ $group ];
        $term         = term_exists( $group_config['slug'], Galeria_Neo_CPT::TAXONOMY );

        if ( ! $term ) {
            $term = term_exists( $group_config['term'], Galeria_Neo_CPT::TAXONOMY );
        }

        if ( ! $term ) {
            $term = wp_insert_term(
                $group_config['term'],
                Galeria_Neo_CPT::TAXONOMY,
                [ 'slug' => $group_config['slug'] ]
            );
        }

        if ( is_wp_error( $term ) ) {
            return 0;
        }

        return absint( is_array( $term ) ? $term['term_id'] : $term );
    }

    public function filter_title_like( string $where, WP_Query $query ): string {
        $patterns = $query->get( 'galeria_neo_title_patterns' );
        if ( empty( $patterns ) || ! is_array( $patterns ) ) {
            return $where;
        }

        global $wpdb;

        $clauses = [];
        $values  = [];
        foreach ( $patterns as $pattern ) {
            $clauses[] = "{$wpdb->posts}.post_title LIKE %s";
            $values[]  = '%' . $wpdb->esc_like( $pattern ) . '%';
        }

        $where .= ' AND (' . $wpdb->prepare( implode( ' OR ', $clauses ), ...$values ) . ')';
        return $where;
    }
}
