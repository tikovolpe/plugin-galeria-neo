<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Widget_Galeria_Neo extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'galeria-neo-widget';
    }

    public function get_title(): string {
        return 'Galeria Neo';
    }

    public function get_icon(): string {
        return 'eicon-gallery-grid';
    }

    public function get_categories(): array {
        return [ 'general' ];
    }

    public function get_keywords(): array {
        return [ 'galeria', 'imagens', 'grid', 'carrossel', 'logos', 'fotos' ];
    }

    public function get_style_depends(): array {
        return [ 'galeria-neo-public' ];
    }

    public function get_script_depends(): array {
        return [ 'galeria-neo-public' ];
    }

    private function get_logo_categories(): array {
        $options = [ '' => 'Todas as categorias' ];
        $terms   = get_terms( [ 'taxonomy' => Galeria_Neo_CPT::TAXONOMY, 'hide_empty' => false ] );
        if ( is_array( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ (string) $term->term_id ] = $term->name;
            }
        }
        return $options;
    }

    protected function register_controls(): void {

        // ══ CONTENT TAB ══════════════════════════════════════════════════

        // ── Logos ─────────────────────────────────────────────────────────
        $this->start_controls_section( 'section_images', [
            'label' => 'Logos',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'logo_category', [
            'label'   => 'Categoria de Logos',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => $this->get_logo_categories(),
        ] );

        $this->add_control( 'order_by', [
            'label'   => 'Ordenar por',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'title',
            'options' => [
                'title' => 'Título',
                'date'  => 'Data',
            ],
        ] );

        $this->add_control( 'order_direction', [
            'label'   => 'Direção',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'asc',
            'options' => [
                'asc'  => 'Crescente (A→Z)',
                'desc' => 'Decrescente (Z→A)',
            ],
        ] );

        $this->end_controls_section();

        // ── Layout ────────────────────────────────────────────────────────
        $this->start_controls_section( 'section_layout', [
            'label' => 'Layout',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_responsive_control( 'display_mode', [
            'label'   => 'Modo de exibição',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'grid',
            'options' => [
                'grid'     => 'Grid',
                'carousel' => 'Carrossel',
            ],
        ] );

        $this->add_responsive_control( 'columns', [
            'label'          => 'Colunas',
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 10,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
        ] );

        $this->add_responsive_control( 'gap', [
            'label'      => 'Espaçamento entre itens (grid)',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
            'default'    => [ 'size' => 16, 'unit' => 'px' ],
        ] );

        $this->end_controls_section();

        // ── Carrossel ─────────────────────────────────────────────────────
        $this->start_controls_section( 'section_carousel', [
            'label' => 'Carrossel',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_responsive_control( 'carousel_columns', [
            'label'     => 'Colunas visíveis',
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 3,
            'default'   => 1,
            'selectors' => [
                '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-carousel-cols: {{VALUE}}',
            ],
        ] );

        $this->add_control( 'carousel_autoplay', [
            'label'        => 'Autoplay',
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => 'Sim',
            'label_off'    => 'Não',
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'carousel_delay', [
            'label'     => 'Intervalo (ms)',
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 500,
            'max'       => 10000,
            'default'   => 3000,
            'condition' => [ 'carousel_autoplay' => 'yes' ],
        ] );

        $this->add_control( 'carousel_transition', [
            'label'   => 'Velocidade de transição (ms)',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 100,
            'max'     => 2000,
            'default' => 500,
        ] );

        $this->end_controls_section();

        // ── Imagem ────────────────────────────────────────────────────────
        $this->start_controls_section( 'section_image_settings', [
            'label' => 'Configurações de imagem',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'image_ratio', [
            'label'   => 'Proporção (aspect-ratio)',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto' => 'Original',
                '1/1'  => '1:1 (quadrado)',
                '4/3'  => '4:3',
                '3/2'  => '3:2',
                '16/9' => '16:9',
                '2/3'  => '2:3 (vertical)',
                '3/4'  => '3:4 (retrato)',
            ],
            'selectors' => [ '{{WRAPPER}} .gn-galeria-item img' => 'aspect-ratio: {{VALUE}}' ],
        ] );

        $this->add_control( 'image_fit', [
            'label'   => 'Preenchimento (object-fit)',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'cover',
            'options' => [
                'cover'   => 'Cover',
                'contain' => 'Contain',
                'fill'    => 'Fill',
            ],
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-fit: {{VALUE}}' ],
        ] );

        $this->end_controls_section();

        // ══ STYLE TAB ════════════════════════════════════════════════════

        // ── Imagem — borda e sombra ────────────────────────────────────────
        $this->start_controls_section( 'section_style_image', [
            'label' => 'Imagem',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_responsive_control( 'image_padding', [
            'label'      => 'Padding interno',
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'selectors'  => [ '{{WRAPPER}} .gn-galeria-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}' ],
        ] );

        $this->add_control( 'image_border_radius', [
            'label'      => 'Borda arredondada',
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'selectors'  => [
                '{{WRAPPER}} .gn-galeria-item'     => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .gn-galeria-item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-radius: {{TOP}}{{UNIT}}',
            ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name'     => 'image_border',
            'selector' => '{{WRAPPER}} .gn-galeria-item',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'image_shadow',
            'selector' => '{{WRAPPER}} .gn-galeria-item',
        ] );

        $this->add_control( 'image_align_h', [
            'label'     => 'Alinhamento horizontal',
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => [ 'title' => 'Esquerda', 'icon' => 'eicon-h-align-left' ],
                'center'     => [ 'title' => 'Centro',   'icon' => 'eicon-h-align-center' ],
                'flex-end'   => [ 'title' => 'Direita',  'icon' => 'eicon-h-align-right' ],
            ],
            'default'   => 'center',
            'selectors' => [ '{{WRAPPER}} .gn-galeria-item' => 'justify-content: {{VALUE}}' ],
        ] );

        $this->add_control( 'image_align_v', [
            'label'     => 'Alinhamento vertical',
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => [ 'title' => 'Topo',   'icon' => 'eicon-v-align-top' ],
                'center'     => [ 'title' => 'Centro', 'icon' => 'eicon-v-align-middle' ],
                'flex-end'   => [ 'title' => 'Base',   'icon' => 'eicon-v-align-bottom' ],
            ],
            'default'   => 'center',
            'selectors' => [ '{{WRAPPER}} .gn-galeria-item' => 'align-items: {{VALUE}}' ],
        ] );

        $this->end_controls_section();

        // ── Hover ─────────────────────────────────────────────────────────
        $this->start_controls_section( 'section_style_hover', [
            'label' => 'Efeito Hover',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'hover_effect', [
            'label'   => 'Efeito',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none'       => 'Nenhum',
                'zoom'       => 'Zoom',
                'overlay'    => 'Overlay (cor)',
                'grayscale'  => 'P&B → Cor',
                'opacity'    => 'Opacidade',
            ],
        ] );

        $this->add_control( 'hover_zoom_scale', [
            'label'     => 'Escala do zoom',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'range'     => [ 'px' => [ 'min' => 1, 'max' => 2, 'step' => 0.01 ] ],
            'default'   => [ 'size' => 1.08 ],
            'condition' => [ 'hover_effect' => 'zoom' ],
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-zoom-scale: {{SIZE}}' ],
        ] );

        $this->add_control( 'hover_overlay_color', [
            'label'     => 'Cor do overlay',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,0.4)',
            'condition' => [ 'hover_effect' => 'overlay' ],
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-overlay-color: {{VALUE}}' ],
        ] );

        $this->add_control( 'hover_opacity_value', [
            'label'     => 'Opacidade no hover',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
            'default'   => [ 'size' => 0.6 ],
            'condition' => [ 'hover_effect' => 'opacity' ],
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-opacity-value: {{SIZE}}' ],
        ] );

        $this->end_controls_section();

        // ── Dots ──────────────────────────────────────────────────────────
        $this->start_controls_section( 'section_style_dots', [
            'label' => 'Dots do Carrossel',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'dot_color', [
            'label'     => 'Cor dos dots',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#cccccc',
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-galeria-dot-color: {{VALUE}}' ],
        ] );

        $this->add_control( 'dot_active_color', [
            'label'     => 'Cor do dot ativo',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#333333',
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-galeria-dot-active-color: {{VALUE}}' ],
        ] );

        $this->add_control( 'dot_size', [
            'label'     => 'Tamanho dos dots',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'range'     => [ 'px' => [ 'min' => 4, 'max' => 24 ] ],
            'default'   => [ 'size' => 10, 'unit' => 'px' ],
            'selectors' => [ '{{WRAPPER}} .galeria-neo-wrapper' => '--gn-galeria-dot-size: {{SIZE}}{{UNIT}}' ],
        ] );

        $this->add_responsive_control( 'dots_margin_top', [
            'label'     => 'Espaçamento acima dos dots',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'range'     => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
            'default'   => [ 'size' => 12, 'unit' => 'px' ],
            'selectors' => [ '{{WRAPPER}} .gn-galeria-dots' => 'margin-top: {{SIZE}}{{UNIT}}' ],
        ] );

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $order_by   = sanitize_text_field( $settings['order_by']        ?? 'title' );
        $order_dir  = strtoupper( sanitize_text_field( $settings['order_direction'] ?? 'asc' ) );
        $cat_id     = sanitize_text_field( $settings['logo_category'] ?? '' );

        $query_args = [
            'post_type'              => Galeria_Neo_CPT::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'date' === $order_by ? 'date' : 'title',
            'order'                  => in_array( $order_dir, [ 'ASC', 'DESC' ], true ) ? $order_dir : 'ASC',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        ];

        if ( '' !== $cat_id ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => Galeria_Neo_CPT::TAXONOMY,
                'field'    => 'term_id',
                'terms'    => (int) $cat_id,
            ] ];
        }

        $logo_query = new WP_Query( $query_args );
        $posts      = $logo_query->posts;

        if ( empty( $posts ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div style="padding:20px; text-align:center; border:1px dashed #ccc;">Nenhum logo encontrado na categoria selecionada.</div>';
            }
            return;
        }

        $mode_desktop = sanitize_text_field( $settings['display_mode'] ?? 'grid' );
        $autoplay     = sanitize_text_field( $settings['carousel_autoplay']  ?? 'yes' );
        $delay        = absint( $settings['carousel_delay']     ?? 3000 );
        $transition   = absint( $settings['carousel_transition'] ?? 500 );
        $hover_effect = sanitize_text_field( $settings['hover_effect'] ?? 'none' );

        $breakpoints_map  = [];
        $mode_data_attrs  = [ 'data-mode-desktop' => esc_attr( $mode_desktop ) ];
        $cols_desktop     = max( 1, min( 3, absint( $settings['carousel_columns'] ?? 1 ) ) );
        $cols_data_attrs  = [ 'data-carousel-cols-desktop' => esc_attr( $cols_desktop ) ];

        if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->breakpoints ) {
            $active_bps = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
            $ordered    = [];
            foreach ( $active_bps as $key => $bp ) {
                $direction = method_exists( $bp, 'get_direction' ) ? $bp->get_direction() : 'max';
                $ordered[] = [ 'key' => $key, 'value' => (int) $bp->get_value(), 'direction' => $direction ];
            }
            usort( $ordered, static fn( $a, $b ) => $a['value'] <=> $b['value'] );

            foreach ( $ordered as $bp ) {
                $key  = $bp['key'];

                $raw  = $settings[ 'display_mode_' . $key ] ?? '';
                $mode = $raw !== '' ? sanitize_text_field( $raw ) : $mode_desktop;
                $mode_data_attrs[ 'data-mode-' . $key ] = esc_attr( $mode );

                $raw_cols = $settings[ 'carousel_columns_' . $key ] ?? '';
                $cols     = $raw_cols !== '' ? max( 1, min( 3, absint( $raw_cols ) ) ) : $cols_desktop;
                $cols_data_attrs[ 'data-carousel-cols-' . $key ] = esc_attr( $cols );

                $breakpoints_map[] = [ 'key' => $key, 'value' => $bp['value'], 'direction' => $bp['direction'] ];
            }
        }

        // Output responsive CSS variables directly — bypasses Elementor CSS compilation issues.
        $el_id        = $this->get_id();
        $selector     = '.elementor-element-' . esc_attr( $el_id ) . ' .galeria-neo-wrapper';
        $cols_desktop_grid = max( 1, absint( $settings['columns'] ?? 3 ) );
        $gap_size     = isset( $settings['gap']['size'] ) ? absint( $settings['gap']['size'] ) : 16;
        $gap_unit     = $settings['gap']['unit'] ?? 'px';

        $css = $selector . '{--gn-cols:' . $cols_desktop_grid . ' !important;--gn-gap:' . $gap_size . $gap_unit . ' !important;}';

        if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->breakpoints ) {
            $active_bps = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
            $ordered_css = [];
            foreach ( $active_bps as $key => $bp ) {
                $direction   = method_exists( $bp, 'get_direction' ) ? $bp->get_direction() : 'max';
                $ordered_css[] = [ 'key' => $key, 'value' => (int) $bp->get_value(), 'direction' => $direction ];
            }
            usort( $ordered_css, static fn( $a, $b ) => $b['value'] <=> $a['value'] );

            foreach ( $ordered_css as $bp ) {
                $key      = $bp['key'];
                $raw_grid = $settings[ 'columns_' . $key ] ?? '';
                if ( $raw_grid === '' ) continue;
                $cols_bp  = max( 1, absint( $raw_grid ) );
                $media    = $bp['direction'] === 'min'
                    ? '@media(min-width:' . $bp['value'] . 'px)'
                    : '@media(max-width:' . $bp['value'] . 'px)';
                $css     .= $media . '{' . $selector . '{--gn-cols:' . $cols_bp . ' !important;}}';
            }
        }

        echo '<style>' . $css . '</style>';

        $wrapper_attrs = array_merge( [
            'class'            => 'galeria-neo-wrapper gn-mode-' . esc_attr( $mode_desktop ) . ' gn-hover-' . esc_attr( $hover_effect ),
            'data-autoplay'    => esc_attr( $autoplay ),
            'data-delay'       => esc_attr( $delay ),
            'data-transition'  => esc_attr( $transition ),
            'data-breakpoints' => esc_attr( wp_json_encode( $breakpoints_map ) ),
        ], $mode_data_attrs, $cols_data_attrs );

        $this->add_render_attribute( 'wrapper', $wrapper_attrs );
        ?>
        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
            <div class="gn-galeria-track">
                <?php foreach ( $posts as $post ) :
                    $thumbnail_id  = get_post_thumbnail_id( $post->ID );
                    $alt           = $thumbnail_id
                        ? esc_attr( get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) )
                        : esc_attr( $post->post_title );
                    $logo_url      = get_post_meta( $post->ID, '_logo_url', true );
                    ?>
                    <div class="gn-galeria-item">
                        <?php if ( $logo_url ) : ?><a href="<?php echo esc_url( $logo_url ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php endif; ?>
                        <?php if ( $thumbnail_id ) : ?>
                            <?php echo wp_get_attachment_image( $thumbnail_id, 'large', false, [ 'alt' => $alt ] ); ?>
                        <?php endif; ?>
                        <?php if ( $logo_url ) : ?></a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( count( $posts ) > 1 ) : ?>
                <div class="gn-galeria-dots" role="tablist" aria-label="Slides do carrossel">
                    <?php foreach ( $posts as $i => $post ) : ?>
                        <button
                            class="gn-galeria-dot<?php echo 0 === $i ? ' gn-galeria-dot-active' : ''; ?>"
                            role="tab"
                            aria-label="<?php echo esc_attr( 'Slide ' . ( $i + 1 ) ); ?>"
                            aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
                        ></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
    }
}
