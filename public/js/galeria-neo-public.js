( function () {
    'use strict';

    class GaleriaNeoCarousel {
        constructor( wrapper, cols = 1 ) {
            this.wrapper     = wrapper;
            this.track       = wrapper.querySelector( '.gn-galeria-track' );
            this.cols        = Math.max( 1, Math.min( 3, cols ) );
            this.autoplay    = wrapper.dataset.autoplay === 'yes';
            this.delay       = parseInt( wrapper.dataset.delay, 10 ) || 3000;
            this.speed       = parseInt( wrapper.dataset.transition, 10 ) || 500;
            this.current     = 0;
            this.isAnimating = false;
            this.timer       = null;
            this.startX      = 0;

            this._buildClones();

            if ( this.total < 2 ) return;

            this._goTo( 0, false );
            this._bindDots();
            this._bindTouch();

            if ( this.autoplay ) {
                this._startAutoplay();
            }
        }

        _buildClones() {
            const originals = Array.from( this.track.querySelectorAll( '.gn-galeria-item:not(.gn-clone)' ) );
            this.total      = Math.ceil( originals.length / this.cols );

            // Show only as many dots as there are pages; hide extras
            this.dots = Array.from( this.wrapper.querySelectorAll( '.gn-galeria-dot' ) );
            this.dots.forEach( ( dot, i ) => {
                dot.style.display = i < this.total ? '' : 'none';
            } );

            if ( this.total < 2 ) return;

            // Clone last `cols` items → prepend (clone of the last page)
            const lastClones = originals.slice( -this.cols ).map( ( el ) => {
                const c = el.cloneNode( true );
                c.classList.add( 'gn-clone' );
                return c;
            } );
            lastClones.reverse().forEach( ( c ) => this.track.insertBefore( c, originals[ 0 ] ) );

            // Clone first `cols` items → append (clone of the first page)
            originals.slice( 0, this.cols ).forEach( ( el ) => {
                const c = el.cloneNode( true );
                c.classList.add( 'gn-clone' );
                this.track.appendChild( c );
            } );
        }

        _goTo( pageIndex, animate = true ) {
            this.track.style.transitionDuration = animate ? this.speed + 'ms' : '0ms';

            // +1 accounts for prepended clone page; px avoids track-width % ambiguity
            const offset = ( pageIndex + 1 ) * this.wrapper.offsetWidth;
            this.track.style.transform = 'translateX(-' + offset + 'px)';

            if ( animate ) {
                this.isAnimating = true;
                setTimeout( () => { this.isAnimating = false; }, this.speed );
            }

            this.current = pageIndex;
            this._updateDots( pageIndex );
        }

        _next() {
            if ( this.isAnimating ) return;

            if ( this.current + 1 >= this.total ) {
                this._goTo( this.total, true );
                setTimeout( () => { this._goTo( 0, false ); }, this.speed );
            } else {
                this._goTo( this.current + 1, true );
            }
        }

        _prev() {
            if ( this.isAnimating ) return;

            if ( this.current - 1 < 0 ) {
                this._goTo( -1, true );
                setTimeout( () => { this._goTo( this.total - 1, false ); }, this.speed );
            } else {
                this._goTo( this.current - 1, true );
            }
        }

        _updateDots( pageIndex ) {
            const real = ( ( pageIndex % this.total ) + this.total ) % this.total;
            this.dots.forEach( ( dot, i ) => {
                if ( i >= this.total ) return;
                dot.classList.toggle( 'gn-galeria-dot-active', i === real );
                dot.setAttribute( 'aria-selected', i === real ? 'true' : 'false' );
            } );
        }

        _bindDots() {
            this.dots.forEach( ( dot, i ) => {
                if ( i >= this.total ) return;
                dot.addEventListener( 'click', () => {
                    this._stopAutoplay();
                    this._goTo( i, true );
                    if ( this.autoplay ) this._startAutoplay();
                } );
            } );
        }

        _bindTouch() {
            this.wrapper.addEventListener( 'touchstart', ( e ) => {
                this.startX = e.touches[ 0 ].clientX;
            }, { passive: true } );

            this.wrapper.addEventListener( 'touchend', ( e ) => {
                const diff = this.startX - e.changedTouches[ 0 ].clientX;
                if ( Math.abs( diff ) > 50 ) {
                    this._stopAutoplay();
                    diff > 0 ? this._next() : this._prev();
                    if ( this.autoplay ) this._startAutoplay();
                }
            }, { passive: true } );
        }

        _startAutoplay() {
            this.timer = setInterval( () => { this._next(); }, this.delay );
        }

        _stopAutoplay() {
            clearInterval( this.timer );
            this.timer = null;
        }

        destroy() {
            this._stopAutoplay();
            this.track.style.transform = '';
            this.track.style.transitionDuration = '';

            // Restore dot visibility
            this.dots.forEach( ( dot ) => { dot.style.display = ''; } );

            // Remove clones
            this.track.querySelectorAll( '.gn-clone' ).forEach( ( el ) => el.remove() );
        }
    }

    // ── Responsive manager ────────────────────────────────────────────
    class GaleriaNeoWidget {
        constructor( wrapper ) {
            this.wrapper      = wrapper;
            this.carousel     = null;
            this._currentCols = null;

            this._apply();

            const debouncedApply = this._debounce( () => { this._apply(); }, 150 );

            if ( typeof ResizeObserver !== 'undefined' ) {
                this._ro = new ResizeObserver( debouncedApply );
                this._ro.observe( document.documentElement );
            } else {
                this._resizeHandler = debouncedApply;
                window.addEventListener( 'resize', this._resizeHandler );
            }
        }

        _getViewportWidth() {
            return document.documentElement.clientWidth || window.innerWidth;
        }

        _getBreakpoints() {
            try {
                const raw = this.wrapper.getAttribute( 'data-breakpoints' );
                if ( raw ) {
                    const list = JSON.parse( raw );
                    return this._sortBreakpoints( list );
                }
            } catch ( e ) { /* ignore */ }

            if ( window.elementorFrontend && elementorFrontend.config && elementorFrontend.config.responsive && elementorFrontend.config.responsive.activeBreakpoints ) {
                const list = [];
                const active = elementorFrontend.config.responsive.activeBreakpoints;
                Object.keys( active ).forEach( ( key ) => {
                    if ( key === 'desktop' ) {
                        return;
                    }

                    list.push( {
                        key: key,
                        value: parseInt( active[ key ].value, 10 ),
                        direction: active[ key ].direction || ( [ 'wide', 'widescreen' ].includes( key ) ? 'min' : 'max' ),
                    } );
                } );
                return this._sortBreakpoints( list );
            }

            return [];
        }

        _sortBreakpoints( list ) {
            return list
                .filter( ( bp ) => bp && bp.key && Number.isFinite( parseInt( bp.value, 10 ) ) )
                .map( ( bp ) => ( {
                    key: bp.key,
                    value: parseInt( bp.value, 10 ),
                    direction: bp.direction === 'min' ? 'min' : 'max',
                } ) )
                .sort( ( a, b ) => {
                    if ( a.direction !== b.direction ) {
                        return a.direction === 'min' ? -1 : 1;
                    }

                    return a.direction === 'min' ? b.value - a.value : a.value - b.value;
                } );
        }

        _getBreakpointKey() {
            const w   = this._getViewportWidth();
            const bps = this._getBreakpoints();

            // Elementor priority:
            // 1. min-width breakpoints (widescreen) - largest value first
            const minBps = bps.filter( bp => bp.direction === 'min' ).sort( ( a, b ) => b.value - a.value );
            for ( let bp of minBps ) {
                if ( w >= bp.value ) return bp.key;
            }

            // 2. max-width breakpoints (mobile, tablet, laptop) - smallest value first
            const maxBps = bps.filter( bp => bp.direction === 'max' ).sort( ( a, b ) => a.value - b.value );
            for ( let bp of maxBps ) {
                if ( w <= bp.value ) return bp.key;
            }

            return 'desktop';
        }

        _getResponsiveAttr( name ) {
            const key = this._getBreakpointKey();
            return this.wrapper.getAttribute( 'data-' + name + '-' + key ) ||
                this.wrapper.getAttribute( 'data-' + name + '-desktop' );
        }

        _getMode() {
            return this._getResponsiveAttr( 'mode' ) || 'grid';
        }

        _getCarouselCols() {
            const key = this._getBreakpointKey();
            const raw = this.wrapper.getAttribute( 'data-carousel-cols-' + key ) ||
                this.wrapper.getAttribute( 'data-carousel-cols-desktop' ) || '1';
            const v = parseInt( raw, 10 );
            return Math.max( 1, Math.min( 3, isNaN( v ) ? 1 : v ) );
        }

        _getItemCount() {
            return this.wrapper.querySelectorAll( '.gn-galeria-track > .gn-galeria-item:not(.gn-clone)' ).length;
        }

        _apply() {
            const mode = this._getMode();
            const cols = mode === 'carousel' ? this._getCarouselCols() : 1;

            this.wrapper.classList.remove( 'gn-mode-grid', 'gn-mode-carousel' );
            this.wrapper.classList.add( 'gn-mode-' + mode );

            if ( mode === 'carousel' && this._getItemCount() > 1 ) {
                if ( ! this.carousel || this._currentCols !== cols ) {
                    if ( this.carousel ) {
                        this.carousel.destroy();
                        this.carousel = null;
                    }
                    this._currentCols = cols;
                    this.carousel = new GaleriaNeoCarousel( this.wrapper, cols );
                }
            } else if ( this.carousel ) {
                this.carousel.destroy();
                this.carousel = null;
                this._currentCols = null;
            }
        }

        destroy() {
            if ( this._ro ) {
                this._ro.disconnect();
            }

            if ( this._resizeHandler ) {
                window.removeEventListener( 'resize', this._resizeHandler );
            }

            if ( this.carousel ) {
                this.carousel.destroy();
                this.carousel = null;
            }
        }

        _debounce( fn, ms ) {
            let t;
            return () => { clearTimeout( t ); t = setTimeout( fn, ms ); };
        }
    }

    // ── Bootstrap ─────────────────────────────────────────────────────
    function initAll( root ) {
        ( root || document ).querySelectorAll( '.galeria-neo-wrapper' ).forEach( ( el ) => {
            if ( el.galeriaNeoWidget ) {
                el.galeriaNeoWidget.destroy();
            }

            el.galeriaNeoWidget = new GaleriaNeoWidget( el );
        } );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', () => initAll() );
    } else {
        initAll();
    }

    // Elementor editor preview
    document.addEventListener( 'DOMContentLoaded', function () {
        if ( ! window.elementorFrontend ) return;
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/galeria-neo-widget.default',
            function ( $scope ) {
                initAll( $scope[ 0 ] );
            }
        );
    } );

} )();
