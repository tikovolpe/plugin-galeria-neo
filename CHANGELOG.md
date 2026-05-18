# Changelog

All notable changes to this project will be documented in this file.

## [1.0.2] - 2026-05-18

### Fixed

- Carrossel: `--gn-carousel-cols` agora definido via `selectors` no control do Elementor, evitando conflito com compilação de CSS do Elementor que quebrava o carrossel
- Carrossel: condição de inicialização corrigida (`itemCount > 1` em vez de `pageCount > 1`), resolvendo falha no mobile onde `offsetWidth` retornava 0
- Widget: geração de CSS por breakpoint simplificada; `carousel_columns` removido do CSS inline PHP (delegado ao Elementor)
- CPT: campo URL no meta box usa `esc_url()` para escape correto de URLs

### Security

- LOW-01: escape de `_logo_url` no ponto de saída do meta box
- LOW-02: escape de `logo_url` no ponto de saída do widget render
- LOW-03: sanitização da chave `$_GET` no filtro de taxonomia (`sanitize_text_field` + `wp_unslash`)
- LOW-04: guard `wp_is_post_revision()` adicionado ao `save_meta`

## [1.0.1] - 2026-05-18

### Fixed

- Correção de permissões do `.htaccess` para compatibilidade com LiteSpeed Cache
- Correção do carrossel mobile: `offsetWidth` retornando 0 na inicialização em produção com HTTPS

## [1.0.0] - 2025-01-01

### Added

- Versão inicial do plugin
