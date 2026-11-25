<?php

// todo: srcset needs h attribute too?

// todo: put this into a function in utility?:
/*

            $sizesToBeFilled = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];

            foreach($sizes as $key => $value) {
                if( array_search($key, $sizesToBeFilled) !== false ){
                    $src = $value;
                    $src = LTUtility::getFullURL($src);
        
                    $srcset = $src.' '.substr($key, 1).'w, ' . $srcset;
                    $ix = array_search($key, $sizesToBeFilled);

                    if($ix !== false){
                        array_splice( $sizesToBeFilled, $ix, 1 );
                    }
                }
            }
            // fill rest of sizes with full image
            for($i=0; $i<count($sizesToBeFilled); $i++){
                $srcset .= $full_src.' '.substr($sizesToBeFilled[$i], 1).'w, ';
            }
            $srcet = substr($srcset, 0, -2);

*/

class LayElFunctions{

    /**
     * Element types that may carry a scroll-driven animation. Keep in sync
     * with `doesElementSupportScrollAnimation` in `gridder/.../sutil.tsx`
     * — the sidebar shouldn't even surface the section for other types.
     * The PHP allowlist is a defensive backstop in case stale data
     * (from import / older versions) carries scrollAnimation on a type
     * the gridder no longer supports.
     */
    public static $scrollAnimationSupportedTypes = array(
        'img',
        'html5video',
        'video',          // YouTube / Vimeo
        'text',
        'postThumbnail',
        'project',        // legacy alias of postThumbnail (see types.tsx)
        'table',
        'marquee',
        'stack',
        'accordion',
        'embed',
        'html',
        'shortcode',
        // Grid types: when `animateIndividualElements` is true on the grid's
        // `scrollAnimation`, the wrap routine below splices the data attrs
        // into each `.element-wrap` / `.thumbnail-wrap` instead of wrapping
        // the whole grid. Otherwise the grid wraps as a single unit.
        'elementgrid',
        'thumbnailgrid',
    );

    public function __construct(){

    }

    /**
     * Read $el['scrollAnimation'] (set by the gridder sidebar) and return both
     * the data-* attributes for the rendered tag and the initial transform
     * value to splice into the inline style. JS at frontend runtime updates
     * the transform per scroll position.
     *
     * Returns: ['attrs' => 'data-lay-scroll-anim="..." …', 'initial_transform' => 'scale(0.9)' or '']
     */
    public static function getScrollAnimationDataAttrs( $el ) {
        $empty = array( 'attrs' => '', 'initial_transform' => '', 'initial_opacity' => '' );
        if ( ! is_array( $el ) ) {
            return $empty;
        }
        if ( ! array_key_exists( 'scrollAnimation', $el ) || ! is_array( $el['scrollAnimation'] ) ) {
            return $empty;
        }
        $sa = $el['scrollAnimation'];
        if ( empty( $sa['enableScrollAnimation'] ) ) {
            return $empty;
        }

        // Sub-feature toggles (booleans). When all are off the master
        // enable still produced no animation — bail out here too.
        $enableScale     = ! empty( $sa['enableScale'] );
        $enableDirection = ! empty( $sa['enableDirection'] );
        $enableSkew      = ! empty( $sa['enableSkew'] );
        $enableRotate    = ! empty( $sa['enableRotate'] );
        $enableFade      = ! empty( $sa['enableFade'] );
        $enableBlur       = ! empty( $sa['enableBlur'] );
        $enableGrayscale  = ! empty( $sa['enableGrayscale'] );
        $enableBrightness = ! empty( $sa['enableBrightness'] );
        $enableReveal     = ! empty( $sa['enableReveal'] );
        if ( ! $enableScale && ! $enableDirection && ! $enableSkew && ! $enableRotate && ! $enableFade && ! $enableBlur && ! $enableGrayscale && ! $enableBrightness && ! $enableReveal ) {
            return $empty;
        }

        // Numbers + strings — clamp client-trusted ranges defensively.
        $scaleFactor = isset( $sa['scaleFactor'] ) ? floatval( $sa['scaleFactor'] ) : 85.0;
        $direction   = isset( $sa['direction'] ) && is_string( $sa['direction'] ) ? $sa['direction'] : 'left';
        $distance    = isset( $sa['distance'] ) ? floatval( $sa['distance'] ) : 100.0;
        $skewFromX   = isset( $sa['skewFromX'] ) ? floatval( $sa['skewFromX'] ) : 0.0;
        $skewFromY   = isset( $sa['skewFromY'] ) ? floatval( $sa['skewFromY'] ) : 0.0;
        $rotateFromX = isset( $sa['rotateFromX'] ) ? floatval( $sa['rotateFromX'] ) : 0.0;
        $rotateFromY = isset( $sa['rotateFromY'] ) ? floatval( $sa['rotateFromY'] ) : 0.0;
        $rotateFromZ = isset( $sa['rotateFromZ'] ) ? floatval( $sa['rotateFromZ'] ) : 0.0;
        $fadeFrom    = isset( $sa['fadeFromOpacity'] ) ? floatval( $sa['fadeFromOpacity'] ) : 0.0;
        $blurFromPx  = isset( $sa['blurFromPx'] ) ? floatval( $sa['blurFromPx'] ) : 10.0;
        $grayscaleFromPct = isset( $sa['grayscaleFromPct'] ) ? floatval( $sa['grayscaleFromPct'] ) : 100.0;
        $brightnessFromPct = isset( $sa['brightnessFromPct'] ) ? floatval( $sa['brightnessFromPct'] ) : 30.0;
        $revealDirection = isset( $sa['revealDirection'] ) && is_string( $sa['revealDirection'] ) ? $sa['revealDirection'] : 'left';
        $endAtTop    = isset( $sa['endAtTop'] ) ? floatval( $sa['endAtTop'] ) : 90.0;
        $easing      = isset( $sa['easing'] ) && is_string( $sa['easing'] ) ? $sa['easing'] : 'easeOutSine';

        if ( $scaleFactor < 0 )   { $scaleFactor = 0; }
        if ( $scaleFactor > 200 ) { $scaleFactor = 200; }
        if ( $distance < 0 )      { $distance = 0; }
        if ( $distance > 100 )    { $distance = 100; }
        if ( $fadeFrom < 0 )      { $fadeFrom = 0; }
        if ( $fadeFrom > 100 )    { $fadeFrom = 100; }
        if ( $blurFromPx < 0 )    { $blurFromPx = 0; }
        if ( $blurFromPx > 200 )  { $blurFromPx = 200; }
        if ( $grayscaleFromPct < 0 )   { $grayscaleFromPct = 0; }
        if ( $grayscaleFromPct > 100 ) { $grayscaleFromPct = 100; }
        if ( $brightnessFromPct < 0 )    { $brightnessFromPct = 0; }
        if ( $brightnessFromPct > 300 )  { $brightnessFromPct = 300; }
        if ( $endAtTop < 0 )      { $endAtTop = 0; }
        if ( $endAtTop > 200 )    { $endAtTop = 200; }
        if ( ! in_array( $direction, array( 'left', 'right', 'bottomLeft', 'bottomRight' ), true ) ) {
            $direction = 'left';
        }
        if ( ! in_array( $revealDirection, array( 'left', 'right', 'top', 'bottom', 'circle' ), true ) ) {
            $revealDirection = 'left';
        }

        // Truthy check that tolerates how the value round-tripped (bool / int /
        // string from JSON encode/decode). Same pattern as text.php's
        // enableScrollScrub handling.
        $sa_truthy = function( $v ) {
            return $v === true || $v === 'true' || $v === 1 || $v === '1';
        };
        $sa_falsy = function( $v ) {
            return $v === false || $v === 'false' || $v === 0 || $v === '0';
        };
        // Scrub mode: when missing, default to true (legacy / pre-toggle data).
        $enableScrub = ! ( isset( $sa['enableScrub'] ) && $sa_falsy( $sa['enableScrub'] ) );

        // Non-scrub timing (only used when $enableScrub === false).
        $top         = isset( $sa['top'] ) ? floatval( $sa['top'] ) : 90.0;
        $duration    = isset( $sa['duration'] ) ? intval( $sa['duration'] ) : 60;
        // Stagger factor: ms per px of diagonal distance to viewport top-left
        // at trigger time. 0 = no stagger; 0.25 = legacy default. Only
        // emitted when > 0 — frontend driver treats absence as off.
        $stagger     = isset( $sa['stagger'] ) ? floatval( $sa['stagger'] ) : 0.0;
        if ( $stagger < 0 ) { $stagger = 0; }
        if ( $top < 0 )       { $top = 0; }
        if ( $top > 100 )     { $top = 100; }
        if ( $duration < 0 )  { $duration = 0; }

        // Master selector + always-on shared settings.
        $attrs  = 'data-lay-scroll-anim="1"';
        if ( $enableScrub ) {
            // Scrub: progress = function of element's scroll position.
            $attrs .= ' data-lay-scroll-anim-end-at-top="' . esc_attr( $endAtTop ) . '"';
            // When endAtFullExit is true, JS overrides endAtTop and ends when the
            // element's bottom edge crosses the viewport top.
            if ( isset( $sa['endAtFullExit'] ) && $sa['endAtFullExit'] === true ) {
                $attrs .= ' data-lay-scroll-anim-end-at-full-exit="1"';
            }
        } else {
            // Non-scrub: animation plays once over `duration` frames after
            // element's top crosses `top%` of the viewport. When `stagger` is
            // on, the JS adds a per-element delay derived from the diagonal
            // distance from the viewport top-left at trigger time (matches
            // the legacy element_animate_in_on_scroll wave effect).
            $attrs .= ' data-lay-scroll-anim-scrub="0"';
            $attrs .= ' data-lay-scroll-anim-trigger-top="' . esc_attr( $top ) . '"';
            $attrs .= ' data-lay-scroll-anim-duration-frames="' . esc_attr( $duration ) . '"';
            // Stagger only matters when there are multiple per-item wraps to
            // distribute delays across — i.e. grids with animateIndividualElements.
            // For a single non-grid element there's nothing to stagger against.
            $emit_stagger = $stagger > 0
                && isset( $sa['animateIndividualElements'] )
                && $sa_truthy( $sa['animateIndividualElements'] );
            if ( $emit_stagger ) {
                $attrs .= ' data-lay-scroll-anim-stagger="' . esc_attr( $stagger ) . '"';
            }
        }
        $attrs .= ' data-lay-scroll-anim-easing="' . esc_attr( $easing ) . '"';

        // "Disable on phone" — JS reads this attr at parse time and bypasses
        // the entire driver for the element when the screen is phone-size.
        if ( isset( $sa['disableOnPhone'] ) && $sa_truthy( $sa['disableOnPhone'] ) ) {
            $attrs .= ' data-lay-scroll-anim-disable-on-phone="1"';
        }

        // Per-feature attrs only when that feature is enabled — JS uses
        // attribute presence to drive its compose logic.
        if ( $enableScale ) {
            $attrs .= ' data-lay-scroll-anim-scale-factor="' . esc_attr( $scaleFactor / 100 ) . '"';
        }
        if ( $enableDirection ) {
            $attrs .= ' data-lay-scroll-anim-direction="' . esc_attr( $direction ) . '"';
            $attrs .= ' data-lay-scroll-anim-distance="' . esc_attr( $distance ) . '"';
        }
        if ( $enableSkew ) {
            $attrs .= ' data-lay-scroll-anim-skew-x="' . esc_attr( $skewFromX ) . '"';
            $attrs .= ' data-lay-scroll-anim-skew-y="' . esc_attr( $skewFromY ) . '"';
        }
        if ( $enableRotate ) {
            $attrs .= ' data-lay-scroll-anim-rotate-x="' . esc_attr( $rotateFromX ) . '"';
            $attrs .= ' data-lay-scroll-anim-rotate-y="' . esc_attr( $rotateFromY ) . '"';
            $attrs .= ' data-lay-scroll-anim-rotate-z="' . esc_attr( $rotateFromZ ) . '"';
        }
        if ( $enableFade ) {
            $attrs .= ' data-lay-scroll-anim-fade-from="' . esc_attr( $fadeFrom / 100 ) . '"';
        }
        if ( $enableBlur ) {
            $attrs .= ' data-lay-scroll-anim-blur-from="' . esc_attr( $blurFromPx ) . '"';
        }
        if ( $enableGrayscale ) {
            $attrs .= ' data-lay-scroll-anim-grayscale-from="' . esc_attr( $grayscaleFromPct ) . '"';
        }
        if ( $enableBrightness ) {
            $attrs .= ' data-lay-scroll-anim-brightness-from="' . esc_attr( $brightnessFromPct ) . '"';
        }
        if ( $enableReveal ) {
            $attrs .= ' data-lay-scroll-anim-reveal="' . esc_attr( $revealDirection ) . '"';
        }

        // Initial inline transform: compose translate + scale at progress=0
        // so the first paint matches what JS will refine to. Translate first,
        // then scale — translate stays in the unscaled coordinate space which
        // matches the JS computation.
        $transform_parts = array();
        if ( $enableDirection ) {
            // Horizontal slides use viewport-relative units (JS refines on
            // first frame using the element's actual natural rect). Diagonals
            // use % of the element's own size — browser handles the math, no
            // refinement needed.
            if ( $direction === 'right' ) {
                $transform_parts[] = 'translate(' . $distance . 'vw, 0)';
            } elseif ( $direction === 'left' ) {
                $transform_parts[] = 'translate(-' . $distance . 'vw, 0)';
            } elseif ( $direction === 'bottomRight' ) {
                $transform_parts[] = 'translate(' . $distance . '%, ' . $distance . '%)';
            } elseif ( $direction === 'bottomLeft' ) {
                $transform_parts[] = 'translate(-' . $distance . '%, ' . $distance . '%)';
            }
        }
        if ( $enableScale ) {
            $transform_parts[] = 'scale(' . ( $scaleFactor / 100 ) . ')';
        }
        if ( $enableSkew ) {
            $transform_parts[] = 'skew(' . $skewFromX . 'deg, ' . $skewFromY . 'deg)';
        }
        if ( $enableRotate ) {
            // 3D rotates need perspective to render with depth — without it
            // CSS reduces them to an orthographic squash. Emit perspective
            // inline before the rotates so the element gets self-perspective.
            $transform_parts[] = 'perspective(1000px) rotateX(' . $rotateFromX . 'deg) rotateY(' . $rotateFromY . 'deg) rotateZ(' . $rotateFromZ . 'deg)';
        }
        $initial_transform = implode( ' ', $transform_parts );

        // Initial opacity if fade is enabled and starts below 100%.
        $initial_opacity = '';
        if ( $enableFade && $fadeFrom < 100 ) {
            $initial_opacity = (string) ( $fadeFrom / 100 );
        }

        // Initial filter / clip-path so first paint matches the JS state.
        $filter_parts = array();
        if ( $enableBlur && $blurFromPx > 0 ) {
            $filter_parts[] = 'blur(' . $blurFromPx . 'px)';
        }
        if ( $enableGrayscale && $grayscaleFromPct > 0 ) {
            $filter_parts[] = 'grayscale(' . $grayscaleFromPct . '%)';
        }
        if ( $enableBrightness ) {
            // Initial = brightnessFromPct/100; final = 1 (animated by JS).
            $filter_parts[] = 'brightness(' . ( $brightnessFromPct / 100 ) . ')';
        }
        $initial_filter = implode( ' ', $filter_parts );

        $initial_clip_path = '';
        if ( $enableReveal ) {
            // Use `polygon()` for the four directional reveals so the JS
            // (which also uses polygon) and the inline initial state agree.
            // Mixing inset() initial with polygon() runtime caused some
            // browsers to silently drop the bottom-only inset clip.
            switch ( $revealDirection ) {
                case 'right':  $initial_clip_path = 'polygon(100% 0, 100% 0, 100% 100%, 100% 100%)'; break;
                case 'top':    $initial_clip_path = 'polygon(0 0, 100% 0, 100% 0, 0 0)'; break;
                case 'bottom': $initial_clip_path = 'polygon(0 100%, 100% 100%, 100% 100%, 0 100%)'; break;
                case 'circle': $initial_clip_path = 'ellipse(0% 0% at 50% 50%)'; break;
                case 'left':
                default:       $initial_clip_path = 'polygon(0 0, 0 0, 0 100%, 0 100%)'; break;
            }
        }

        return array(
            'attrs'             => $attrs,
            'initial_transform' => $initial_transform,
            'initial_opacity'   => $initial_opacity,
            'initial_filter'    => $initial_filter,
            'initial_clip_path' => $initial_clip_path,
        );
    }

    public static function shouldApplyScrollAnimationToColumn( $el ) {
        if ( ! is_array( $el ) ) {
            return false;
        }
        $type = isset( $el['type'] ) ? $el['type'] : '';
        if ( $type !== 'text' && $type !== 'stack' ) {
            return false;
        }
        if ( ! isset( $el['scrollAnimation'] ) || ! is_array( $el['scrollAnimation'] ) || empty( $el['scrollAnimation']['enableScrollAnimation'] ) ) {
            return false;
        }
        if ( isset( $el['bgColor'] ) && is_array( $el['bgColor'] ) && ! empty( $el['bgColor']['enableBgColor'] ) ) {
            $opacity = isset( $el['bgColor']['bgOpacity'] ) ? (float) $el['bgColor']['bgOpacity'] : 100;
            if ( $opacity > 0 ) {
                return true;
            }
        }
        if ( isset( $el['border'] ) && is_array( $el['border'] ) && ! empty( $el['border']['enableBorder'] ) ) {
            $opacity = isset( $el['border']['borderOpacity'] ) ? (float) $el['border']['borderOpacity'] : 100;
            $widths = isset( $el['border']['borderWidth'] ) && is_array( $el['border']['borderWidth'] ) ? $el['border']['borderWidth'] : array();
            foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
                if ( $opacity > 0 && isset( $widths[$side] ) && (float) $widths[$side] > 0 ) {
                    return true;
                }
            }
        }
        if ( isset( $el['shadow'] ) && is_array( $el['shadow'] ) && ! empty( $el['shadow']['enableShadow'] ) ) {
            $opacity = isset( $el['shadow']['opacity'] ) ? (float) $el['shadow']['opacity'] : 100;
            if ( $opacity > 0 ) {
                return true;
            }
        }
        if ( isset( $el['borderRadius'] ) && is_array( $el['borderRadius'] ) ) {
            $br = $el['borderRadius'];
            foreach ( array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' ) as $corner ) {
                if ( isset( $br[$corner] ) && (float) $br[$corner] > 0 ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function getScrollAnimationInitialStyleCss( $scroll_animation_data, $base_transform = '', $base_opacity = '' ) {
        if ( ! is_array( $scroll_animation_data ) ) {
            return '';
        }
        $style_css = '';
        $transform = '';
        if ( is_string( $base_transform ) && trim( $base_transform ) !== '' ) {
            $transform = trim( $base_transform );
        }
        if ( isset( $scroll_animation_data['initial_transform'] ) && $scroll_animation_data['initial_transform'] !== '' ) {
            $transform = trim( $transform . ' ' . $scroll_animation_data['initial_transform'] );
        }
        if ( $transform !== '' ) {
            $style_css .= 'transform:' . $transform . ';transform-origin:center center;';
        }
        $opacity = '';
        if ( is_string( $base_opacity ) && trim( $base_opacity ) !== '' ) {
            $opacity = trim( $base_opacity );
        }
        if ( isset( $scroll_animation_data['initial_opacity'] ) && $scroll_animation_data['initial_opacity'] !== '' ) {
            $scroll_opacity = (float) $scroll_animation_data['initial_opacity'];
            if ( $opacity !== '' && is_numeric( $opacity ) ) {
                $opacity = (string) ( (float) $opacity * $scroll_opacity );
            } else {
                $opacity = (string) $scroll_opacity;
            }
        }
        if ( $opacity !== '' ) {
            $style_css .= 'opacity:' . $opacity . ';';
        }
        if ( isset( $scroll_animation_data['initial_filter'] ) && $scroll_animation_data['initial_filter'] !== '' ) {
            $style_css .= 'filter:' . $scroll_animation_data['initial_filter'] . ';';
        }
        if ( isset( $scroll_animation_data['initial_clip_path'] ) && $scroll_animation_data['initial_clip_path'] !== '' ) {
            $style_css .= 'clip-path:' . $scroll_animation_data['initial_clip_path'] . ';';
        }
        return $style_css;
    }

    /**
     * Centralised scroll-animation outer wrap. Called once from
     * LayEl::getMarkup so any supported element type animates without
     * its renderer needing to know about scroll animations. Frontend JS
     * (`scroll_animations.js`) targets `[data-lay-scroll-anim="1"]`, so
     * the wrapper is the animation subject.
     *
     * Returns the markup unchanged when scroll animation is not enabled
     * or when the element type isn't in the allowlist.
     */
    public static function wrapWithScrollAnimation( $markup, $el ) {
        if ( ! is_array( $el ) ) {
            return $markup;
        }
        $type = isset( $el['type'] ) ? $el['type'] : '';
        if ( ! in_array( $type, self::$scrollAnimationSupportedTypes, true ) ) {
            return $markup;
        }
        if ( self::shouldApplyScrollAnimationToColumn( $el ) ) {
            return $markup;
        }
        // Grid + `animateIndividualElements`: don't wrap the whole grid here
        // — the renderers in `elementgrid.php` / `thumbnailgrid.php` already
        // wrapped EACH inner item via `wrapMarkupWithScrollAnimationFromEl`
        // using the grid's scrollAnimation config. Wrapping the grid AGAIN
        // here would either double-animate or fight masonry's positioning
        // styles on `.thumbnail-wrap` items.
        $animateIndividual = ( $type === 'elementgrid' || $type === 'thumbnailgrid' )
            && isset( $el['scrollAnimation'] )
            && is_array( $el['scrollAnimation'] )
            && ! empty( $el['scrollAnimation']['animateIndividualElements'] );
        if ( $animateIndividual ) {
            return $markup;
        }

        return self::wrapMarkupWithScrollAnimationFromEl( $markup, $el );
    }

    /**
     * Build the actual `<div class="lay-scroll-anim-wrap" data-... style="...">…</div>`
     * wrapper from a scrollAnimation-bearing $el. Used both by the standard
     * per-element path (`wrapWithScrollAnimation` above) AND by the grid
     * renderers when `animateIndividualElements` is true (each cell wraps
     * itself directly so we don't have to retrofit attrs onto existing
     * `.element-wrap`/`.thumbnail-wrap` markup that may already carry a
     * `style="display:none"` etc.).
     */
    public static function wrapMarkupWithScrollAnimationFromEl( $markup, $el ) {
        if ( ! is_array( $el ) ) {
            return $markup;
        }
        $sa = self::getScrollAnimationDataAttrs( $el );
        if ( $sa['attrs'] === '' ) {
            return $markup;
        }
        $style_css = self::getScrollAnimationInitialStyleCss( $sa );
        $style_attr = $style_css !== '' ? ' style="' . $style_css . '"' : '';
        return '<div class="lay-scroll-anim-wrap"' . $style_attr . ' ' . $sa['attrs'] . '>' . $markup . '</div>';
    }

    public static function getUnmuteButtonMarkup( $model ){
        $unmuteButtonStyle = 1;
        if( array_key_exists('unmuteButtonStyle', $model) ){
            $unmuteButtonStyle = $model['unmuteButtonStyle'];
        }
        $unmute_button_position = array_key_exists('unmuteButtonPosition', $model) ? $model['unmuteButtonPosition'] : 'bottom-right';
        if( !in_array($unmute_button_position, array('top-left', 'top-right', 'bottom-left', 'bottom-right'), true) ){
            $unmute_button_position = 'bottom-right';
        }
        $invert_icon_color = array_key_exists('invertIconColor', $model) && ($model['invertIconColor'] === true || $model['invertIconColor'] === 'true' || $model['invertIconColor'] == 1);
        $use_custom_icons = get_option( 'misc_options_use_custom_mute_unmute_icons', '' ) == 'on';
        $button_inline_style = '';
        $button_class_suffix = '';
        $volume_off_markup = '<svg class="lay-volume-off" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 5L6 9H2V15H6L11 19V5Z" fill="white"/>
                <path d="M23 9L17 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17 9L23 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>';
        $volume_on_markup = '<svg class="lay-volume-on" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 5L6 9H2V15H6L11 19V5Z" fill="white"/>
                <path d="M15.54 8.46C16.4774 9.39764 17.004 10.6692 17.004 11.995C17.004 13.3208 16.4774 14.5924 15.54 15.53" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M19.07 4.93C20.9447 6.80528 21.9979 9.34836 21.9979 12C21.9979 14.6516 20.9447 17.1947 19.07 19.07" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>';

        if( $use_custom_icons ){
            $volume_off_icon = get_template_directory_uri().'/frontend/assets/img/volume-off.svg';
            $volume_on_icon = get_template_directory_uri().'/frontend/assets/img/volume-on.svg';
            $volume_off_width = 20;
            $volume_off_height = 20;
            $volume_on_width = 20;
            $volume_on_height = 20;

            $volume_off_icon_id = get_option( 'misc_options_muted_state_icon', '' );
            if( $volume_off_icon_id != '' ){
                $img = wp_get_attachment_image_src( $volume_off_icon_id, 'full' );
                if( is_array($img) ){
                    $volume_off_icon = $img[0];
                    if( isset($img[1]) && isset($img[2]) && (int)$img[1] > 0 && (int)$img[2] > 0 ){
                        $volume_off_width = (int)$img[1];
                        $volume_off_height = (int)$img[2];
                    }
                }
                if( $volume_off_width <= 0 || $volume_off_height <= 0 ){
                    $meta = wp_get_attachment_metadata( $volume_off_icon_id );
                    if( is_array($meta) && isset($meta['width']) && isset($meta['height']) ){
                        $volume_off_width = (int)$meta['width'];
                        $volume_off_height = (int)$meta['height'];
                    }
                }
            }
            $volume_on_icon_id = get_option( 'misc_options_unmuted_state_icon', '' );
            if( $volume_on_icon_id != '' ){
                $img = wp_get_attachment_image_src( $volume_on_icon_id, 'full' );
                if( is_array($img) ){
                    $volume_on_icon = $img[0];
                    if( isset($img[1]) && isset($img[2]) && (int)$img[1] > 0 && (int)$img[2] > 0 ){
                        $volume_on_width = (int)$img[1];
                        $volume_on_height = (int)$img[2];
                    }
                }
                if( $volume_on_width <= 0 || $volume_on_height <= 0 ){
                    $meta = wp_get_attachment_metadata( $volume_on_icon_id );
                    if( is_array($meta) && isset($meta['width']) && isset($meta['height']) ){
                        $volume_on_width = (int)$meta['width'];
                        $volume_on_height = (int)$meta['height'];
                    }
                }
            }

            $button_width = max( $volume_off_width, $volume_on_width );
            $button_height = max( $volume_off_height, $volume_on_height );
            if( $button_width > 0 && $button_height > 0 ){
                $button_inline_style = ' style="width:'.$button_width.'px;height:'.$button_height.'px;"';
            }
            $button_class_suffix = ' lay-mute-unmute-button-custom-icons';

            $invert_filter_style = $invert_icon_color ? ';filter:invert(1)' : '';
            $volume_off_markup = '<img class="lay-volume-off" src="'.esc_url($volume_off_icon).'" alt="" style="width:'.$volume_off_width.'px;height:'.$volume_off_height.'px'.$invert_filter_style.';" />';
            $volume_on_markup = '<img class="lay-volume-on" src="'.esc_url($volume_on_icon).'" alt="" style="width:'.$volume_on_width.'px;height:'.$volume_on_height.'px'.$invert_filter_style.';" />';
        }

        return
        '<div class="lay-mute-unmute-button lay-unmute-button-position-'.$unmute_button_position.' lay-unmute-button-style-'.$unmuteButtonStyle.$button_class_suffix.'"'.$button_inline_style.'>
            '.$volume_off_markup.'
            '.$volume_on_markup.'
        </div>';
    }

    public static function getAnchorDataAttributesById($id){
        $type = get_post_type($id);
        if($type == 'post'){
            $type = 'project';
        }

        $title = get_the_title($id);
        $slug = get_post_field('post_name', $id);
        $catid = wp_get_post_categories($id, array('fields'=>'ids'));

        $dataAttributes = '';
        $dataAttributes .= 'data-id="'.$id.'"';
        $dataAttributes .= ' data-type="'.$type.'"';
        $dataAttributes .= ' data-title="'.$title.'"';
        $dataAttributes .= ' data-catid="'.LayElFunctions::stringifyCatIds($catid).'"';
        $dataAttributes .= ' data-slug="'.$slug.'"';
        return $dataAttributes;
    }

    // note to myself: html5 video in thumbnail.php is not generated by this
    /**
     * Validate and enforce incompatible HTML5 video checkbox combinations
     * Ensures that conflicting settings cannot be active simultaneously
     * 
     * @param array $model Video model array (passed by reference, will be modified)
     * @param array|null $videoConfig Optional video config array (passed by reference, will be modified if provided)
     */
    private static function validateHTML5VideoSettings(&$model, &$videoConfig = null) {
        $playpauseonclick = array_key_exists('playpauseonclick', $model) ? $model['playpauseonclick'] : false;
        $customLayControls = array_key_exists('customLayControls', $model) ? $model['customLayControls'] : false;
        $controls = array_key_exists('controls', $model) ? $model['controls'] : false;
        $unmutebutton = array_key_exists('unmutebutton', $model) ? $model['unmutebutton'] : false;
        
        // If playpauseonclick is enabled, disable incompatible options
        if ($playpauseonclick) {
            $model['controls'] = false;
            $model['customLayControls'] = false;
            // Note: unmutebutton is allowed with playpauseonclick as it only controls mute/unmute
            // Also update videoConfig if it exists
            if ($videoConfig !== null && array_key_exists('customLayControls', $videoConfig)) {
                $videoConfig['customLayControls'] = false;
            }
        }
        
        // If customLayControls is enabled, disable incompatible options
        if ($customLayControls) {
            $model['controls'] = false;
            $model['playpauseonclick'] = false;
            $model['unmutebutton'] = false;
        }
        
        // If controls is enabled, disable incompatible options
        if ($controls) {
            $model['customLayControls'] = false;
            $model['unmutebutton'] = false;
            // Also update videoConfig if it exists
            if ($videoConfig !== null && array_key_exists('customLayControls', $videoConfig)) {
                $videoConfig['customLayControls'] = false;
            }
        }
    }

    public static function getHTML5VideoMarkup($videoConfig) {
        $model = $videoConfig['model'];

        // Validate and enforce incompatible checkbox combinations
        self::validateHTML5VideoSettings($model, $videoConfig);

        $useExternalVideo = array_key_exists('useExternalVideo', $model) && $model['useExternalVideo'] == true ? true : false;
        $poster = '';
        $lazyload = !$videoConfig['noLazyLoad'];
        $lazyLoadClass = $lazyload == true ? 'video-lazyload' : 'no-lazyload';

        // using new lazyload library:
        // $lazyLoadClass = $lazyload == true ? 'lazyload' : 'no-lazyload';
        $playsinline = 'playsinline';

        $loadedClass = $lazyload ? '' : 'loaded';
    
        $markup = '';

        $pb = $useExternalVideo ? (int)$model['externalVideoH'] / (int)$model['externalVideoW'] * 100 : $model['ar'] * 100;
        $autoplay_class = $model['autoplay'] == true ? 'autoplay' : '';

        // cannot do "autoplay" because this would make 'preload="none"' not work.
        /*
        The autoplay attribute has precedence over preload. If autoplay is specified, the browser would obviously need to start downloading the video for playback.
        */
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/video
        $autoplay_attr = $model['autoplay'] == true ? 'data-autoplay' : '';
        
        if( $lazyload == false && $model['autoplay'] == true ) {
            $autoplay_attr = 'autoplay';
        }

        // preload="none" is ignored when autoplay is on
        // when having a desktop and cpl layout, this will load all videos on an iphone but
        // i need to do this for now, because somehow, html5 videos didnt load at all anymore on iphones
        // if( LTUtility::$isTouchDevice ) {
        //     $autoplay_attr = $model['autoplay'] == true ? 'autoplay' : '';
        // }
        
        $mute = $model['mute'] == true ? 'muted' : '';
        // every autoplay video must be muted, otherwise safari will not autoplay it when scrolled to in "play_pause_autoplay_videos_on_scroll.js"
        if($model['autoplay'] == true){
            $mute = 'muted';
        }
    
        $loop = $model['loop'] == true ? 'loop' : '';
        $controls = $model['controls'] == true ? 'controls' : '';
        $playIcon = "";
        if( $model['playicon'] == true ){
            $src = get_template_directory_uri().'/frontend/assets/img/play.svg';
            if(LayFrontend_Options::$playicon != ""){
                $src = LayFrontend_Options::$playicon;
            }
            $playIcon = '<img class="html5video-customplayicon" src="'.$src.'">';
        }

        $playpauseonclick = array_key_exists('playpauseonclick', $model) ? $model['playpauseonclick'] : false;
        $playpauseonclick_class = 'playpauseonclick';
    
        if( $playpauseonclick ) {
            $playpauseonclick = true;
        }
    
        if( $playpauseonclick == false){
            $playpauseonclick_class = '';
        }
        
        $ar = $useExternalVideo ? (int)$model['externalVideoH'] / (int)$model['externalVideoW'] : $model['ar'];
        $sizingClass = $ar > $model['imgar'] ? 'h100' : 'w100';

        $placeholderImage = "";
        $html_class = '';
        $usePlaceholderImageDiv = true; // Flag to track if we should use the div or just poster attribute
        
        if($videoConfig['showPlaceholderImage'] == true){
            // If standard controls are enabled, or caller requests it (e.g. carousel), use poster attribute instead of placeholder image div
            $usePosterAttribute = !empty($videoConfig['usePosterAttribute']);
            if($model['controls'] == true || $usePosterAttribute){
                $usePlaceholderImageDiv = false;
                // Poster will be set below based on image sizes
            } else {
                // clone
                $newmodel = json_decode( json_encode($model), true);
                // changing keys to fit for "getLazyImg"
                // setting imgar to ar, otherwise the lazy sizes image's ar is just the video's ar
                if( array_key_exists('imgar', $model) ){
                    $imgar = $model['imgar'];
                    $newmodel['ar'] = $imgar;
                }
                if( array_key_exists('imgattid', $model) ){
                    $imgattid = $model['imgattid'];
                    $newmodel['attid'] = $imgattid;
                }

                $html_class .= 'has-placeholder-image';
                $placeholderImage = '<div class="html5-video-placeholder-image '.$sizingClass.' '.$playpauseonclick_class.'">'.LayElFunctions::getLazyImg($newmodel).'</div>';
            }
        }

        // Set poster attribute when using standard controls, when autoplay is enabled, or when caller requests it (e.g. carousel usePosterAttribute)
        if( is_array($model['sizes']) && ($model['controls'] == true || $model['autoplay'] == true || !empty($videoConfig['usePosterAttribute'])) && $videoConfig['showPlaceholderImage'] == true ) {
            $sizes = $model['sizes'];
            $full = '';
            if( array_key_exists('imgattid', $model) ){
                $imgattid = $model['imgattid'];
                $full = wp_get_attachment_image_src( $imgattid, 'full' );
                if(is_array($full)) {
                    $full = $full[0];
                }
            }else{
                $full = LTUtility::getFullURL($sizes['full']);
            }
    
            $poster = 'poster="'.$full.'"';
            if( array_key_exists('_1024', $sizes) ) {
                $_1024 = '';
                if( array_key_exists('imgattid', $model) ){
                    $_1024 = wp_get_attachment_image_src( $imgattid, '_1024' );
                    if( $_1024 != false ) {
                        $_1024 = $_1024[0];
                    } else {
                        $_1024 = $full;
                    }
                }else{
                    $_1024 = LTUtility::getFullURL($sizes['_1024']);
                }
                
                $poster = 'poster="'.$_1024.'"';
            }
        }
        
        // don't need poster when I'm not on touch device
        // if ( LTUtility::$isTouchDevice == false ) {
        // 	$poster = '';
        // }
        // else if( LTUtility::$isTouchDevice && $model['autoplay'] == true ) {
        // if( LTUtility::$isTouchDevice && $model['autoplay'] == true ) {
            // if i'm on touchdevice, do not show "html5-video-placeholder-image" when autoplay is on
            // what do I need "html5-video-placeholder-image" for? Why not just use the "poster" attribute only?
            // probably for having a nice fade-in effect when the video loads and srcset
            // answer: for being able to load responsive sized images
            // $placeholderImage = "";
        // }
        // if( LTUtility::$supportsPlaysInlineOnTouchDevice && $model['autoplay'] == true ) {
            // no need for poster here
            // $poster = '';
        // }

        // when autoplay is on, use low-res poster attribute
        // when autoplay is off, use high-res placeholder image that fades in too
        // BUT: if standard controls are enabled, use poster attribute instead of placeholder image div
        if( $model['autoplay'] == true ) {
            $placeholderImage = "";
        } else {
            // Only clear poster if we're using the placeholder image div (not using standard controls)
            if($usePlaceholderImageDiv) {
                $poster = '';
            }
        }

        $mp4 = '';
        if($useExternalVideo){
            $mp4 = $model['externalVideoUrl'];
        } else {
            $mp4 = $model['mp4'];
            if( array_key_exists('mp4attid', $model) ) {
                $mp4 = wp_get_attachment_url($model['mp4attid']);
                // if we have the mp4 att id, we can set the width and height again, to make sure we got the correct one. maybe thy have changed width and height in media library
                // get metadata from attachment
                $metadata = wp_get_attachment_metadata($model['mp4attid']);
                if( $metadata != false && array_key_exists('width', $metadata) && array_key_exists('height', $metadata) ) {
                    $model['w'] = $metadata['width'];
                    $model['h'] = $metadata['height'];
                    $pb = (int)$model['h'] / (int)$model['w'] * 100;
                }
            } else {
                $mp4 = LTUtility::getFullURL($mp4);
            }
        }

    
        $useWrap = true;
        if( array_key_exists('useWrap', $videoConfig) ){
            if( $videoConfig['useWrap'] == false ) {
                $useWrap = false;
            }
        }

        $useMuteUnmuteButton = false;
        if( array_key_exists('unmutebutton', $model) ){
            if( $model['unmutebutton'] == true ) {
                $useMuteUnmuteButton = true;
            }
        }

        $usePaddingPlaceholder = ( array_key_exists('usePaddingPlaceholder', $videoConfig) && $videoConfig['usePaddingPlaceholder'] ) ? true : false;
        $fixedHeightEnabled = false;
        $fixedHeightStyle = '';
        if(array_key_exists('fixedHeight', $model) && array_key_exists('enableFixedHeight', $model['fixedHeight']) && $model['fixedHeight']['enableFixedHeight']) {
            $fixedHeightEnabled = true;
            $fixedHeight = $model['fixedHeight'];
            $fixedHeightStyle = ' style="height: calc('.$fixedHeight['height'].');"';
            $usePaddingPlaceholder = false;
        }
        
        $openingAnchor = '';
        $endingAnchor = '';
        $linkUrl = '';

        $imageLink = array_key_exists('imagelink', $model) ? $model['imagelink'] : false;
        if( $imageLink != false ){
            if( is_array($imageLink) ) {
                $linkUrl = LTUtility::getFullURL($imageLink['url']);
                // is this not a link to an internal page / category / post?
                if( $imageLink['type'] == '' ) {
                    // need to use the real URL and not change it through "LTUtility::getFullURL"
                    $linkUrl = $imageLink['url'];
                }
                $openingAnchor = '<a href="'.$linkUrl.'"';
                if($imageLink['newtab'] == true){
                    $openingAnchor .= ' target="_blank"';
                } else {
                    if( $imageLink['id'] != '' ) {
                        $openingAnchor .= ' data-id="'.$imageLink['id'].'"';
                    }
                    if( $imageLink['type'] != '' ) {
                        $openingAnchor .= ' data-type="'.$imageLink['type'].'"';
                    }
                    if( $imageLink['title'] != '' ) {
                        $openingAnchor .= ' data-title="'.__($imageLink['title']).'"';
                    }
                    if( array_key_exists('catid', $imageLink) && $imageLink['catid'] != '' ) {
                        $openingAnchor .= ' data-catid="'.LayElFunctions::stringifyCatIds($imageLink['catid']).'"';
                    }
                }
            } else {
                // for backwards compatibility
                $newtab = array_key_exists('newtab', $model) ? $model['newtbab'] : false;
                $linkUrl = LTUtility::getFullURL($imageLink);
                $openingAnchor = '<a href="'.$linkUrl.'"'; 
                if( $newtab == true ) {
                    $openingAnchor .= ' target="_blank"';
                }
            }
            $openingAnchor .= '>';
            $endingAnchor = '</a>';

            if($linkUrl == '' || $linkUrl == 'null' || $linkUrl == null){
                $linkUrl = '';
                $openingAnchor = '';
                $endingAnchor = '';
            }
        }



        /*
            useExternalVideo: false,
            externalVideoUrl: '',
            externalVideoW: '',
            externalVideoH: '',
        */
        $orientation = '';
        if($useExternalVideo){
            $orientation = $model['externalVideoW'] > $model['externalVideoH'] ? 'landscape' : 'portrait';
        } else {
            if( array_key_exists('w', $model) && array_key_exists('h', $model) ){
                $orientation = $model['w'] > $model['h'] ? 'landscape' : 'portrait';
            }
        }

        $customLayControlsClass = '';
        // Use the validated value from model (after validation above)
        $customLayControlsValidated = array_key_exists('customLayControls', $model) ? $model['customLayControls'] : false;
        if($customLayControlsValidated == true) {
            $customLayControlsClass = 'lay-vimeo-custom-controls';
        }

        $markup = '';
        if( $useWrap ){
            // Scroll-driven animations are no longer applied here — they're
            // applied centrally by LayEl::getMarkup → LayElFunctions::wrapWithScrollAnimation
            // which wraps the whole element markup so the placeholder, caption,
            // and unmute button all scale/fade together with the video.
            $markup .= '<div class="html5video '.$orientation.' '.$autoplay_class.' '.$html_class.' '.$customLayControlsClass.'"'.$fixedHeightStyle.'>';
        }
        $markup .= $openingAnchor;
        if( $usePaddingPlaceholder ){
            $markup .= '<div class="ph" style="padding-bottom:'.Lay_LayoutFunctions::replaceCommaWithDot($pb).'%;">';
        }

        $w = '';
        $h = '';
        if($useExternalVideo){
            $w = 'data-w="'.$model['externalVideoW'].'"';
            $h = 'data-h="'.$model['externalVideoH'].'"';
        } else {
            $w = array_key_exists('w', $model) ? 'data-w="'.$model['w'].'"' : '';
            $h = array_key_exists('h', $model) ? 'data-h="'.$model['h'].'"' : '';
        }


        $style = '';
        if($useExternalVideo){
            $style = 'style="aspect-ratio:'.$model['externalVideoW'].'/'.$model['externalVideoH'].';"';
        } else {
            if(array_key_exists('w', $model) && array_key_exists('h', $model)) {
                $style = 'style="aspect-ratio:'.$model['w'].'/'.$model['h'].';"';
            }
        }
        if($fixedHeightEnabled) {
            $fixedHeight = $model['fixedHeight'];
            $style = 'style="width:100%; height:100%; object-fit:'.$fixedHeight['objectFit'].'; object-position:'.$fixedHeight['objectPosition'].';"';
        }

        $preload = 'preload="auto"';



        $ar = '';
        if($useExternalVideo){
            $ar = $model['externalVideoH'] / $model['externalVideoW'];
        } else {
            $ar = $model['ar'];
        }


        $video_type = LTUtility::getVideoMimeTypeFromUrl($mp4);

        $markup .=
            $playIcon.$placeholderImage
            .'<video '.$preload.' '.$w.' '.$h.' '.$style.' data-ar="'.$ar.'" '.$poster.' '.$playsinline.' '.$autoplay_attr.' '.$mute.' '.$loop.' '.$controls.' class="'.$autoplay_class.' '.$lazyLoadClass.' '.$playpauseonclick_class.' '.$loadedClass.'">';
                if( $lazyload ) {
                    $markup .= '<source data-src="'.$mp4.'" type="'.$video_type.'">';    
                } else {
                    $markup .= '<source src="'.$mp4.'" type="'.$video_type.'">';    
                }
        $markup .=
            '</video>';
        if( $useMuteUnmuteButton ) {
            $markup .= LayElFunctions::getUnmuteButtonMarkup($model);
        }
        $overlay_options = array('grid_container_selector' => isset($videoConfig['grid_container_selector']) ? $videoConfig['grid_container_selector'] : '');
        $markup .= LayElFunctions::getTextOnElementGradientMarkup($model);
        $markup .= LayElFunctions::getTextOnElementOverlayMarkup($model, $overlay_options);
        if( $usePaddingPlaceholder ){
            $markup .= '</div>';
        }
        $markup .= $endingAnchor;
        $markup .= $videoConfig['captionMarkup'];
        if( $useWrap ){
            $markup .= '</div>';
        }
        
        return $markup.$endingAnchor;
    }

    public static function getHTML5VideoMarkupSimple($model){
    /*
        $model['mute'] = true;
        $model['playpauseonclick'] = false;
        $model['autoplay'] = false;
        $model['controls'] = false;
        $model['playicon'] = false;
        $model['ar']
    */    

        // Validate and enforce incompatible checkbox combinations
        self::validateHTML5VideoSettings($model);

        $inside_thumbnailgrid_with_filter = false;
        $inside_thumbnailgrid_with_filter_class = '';

        if( array_key_exists('inside_thumbnailgrid_with_filter', $model) && $model['inside_thumbnailgrid_with_filter'] == true ){
            $inside_thumbnailgrid_with_filter = true;
            $inside_thumbnailgrid_with_filter_class = 'video-inside-thumbnailgrid-with-filter';
        }


        // $lazyload = !$videoConfig['noLazyLoad'];
        // $lazyload = Setup::$is_firefox ? false : true;

        $lazyload = true;
        if( Setup::$is_firefox == true && LayFrontend_Options::$enable_video_lazyloading_for_firefox == false ) {
            $lazyload = false;
        }
        if( Setup::$is_safari == true && LayFrontend_Options::$enable_video_lazyloading_for_safari == false ) {
            $lazyload = false;
        }
        if( Setup::$is_instagram == true) {
            $lazyload = false;
        }
        if(LayFrontend_Options::$videos_lazyload == false) {
            $lazyload = false;
        }

        $lazyLoadClass = $lazyload == true ? 'video-lazyload' : 'no-lazyload';

        $preload = $lazyload ? 'preload="metadata"' : '';
        // $lazyLoadClass = 'video-lazyload';
        $autoplay_class = $model['autoplay'] == true ? 'autoplay' : '';
        // $autoplay_attr = $model['autoplay'] == true ? 'data-autoplay' : '';
        $autoplay_attr = $model['autoplay'] == true ? 'autoplay' : '';
        $mute = $model['mute'] == true ? 'muted' : '';
        $loop = $model['loop'] == true ? 'loop' : '';
        $playsinline = 'playsinline';
        $mp4 = $model['mp4'];
        $mp4 = LTUtility::getFullURL($mp4);

        $style = '';
        if(array_key_exists('w', $model) && array_key_exists('h', $model)) {
            $style = 'style="aspect-ratio:'.$model['w'].'/'.$model['h'].';"';
        }

        $video_type = LTUtility::getVideoMimeTypeFromUrl($mp4);
        $markup = 
        '<video '.$style.' '.$preload.' '.$playsinline.' '.$autoplay_attr.' '.$mute.' '.$loop.' class="'.$autoplay_class.' '.$lazyLoadClass.' '.$inside_thumbnailgrid_with_filter_class.'">';
        if( $lazyload ) {
            $markup .= '<source data-src="'.$mp4.'" type="'.$video_type.'">';  
        } else {
            $markup .= '<source src="'.$mp4.'" type="'.$video_type.'">';  
        }
        $markup .= '</video>';
        return $markup;
    }

    public static function getLazyImg($el){
        if( $el['sizes']['full'] == null ){
            // image does not exist
            return;
        }
        // Todo: We set the data-id attribute of the img to $attid,
        // what happens in imagehover plugin if there is no id -> attid = false?
        $attid = false;
        $alt = MiscOptions::$image_alt_tag == '' ? get_bloginfo('name') : MiscOptions::$image_alt_tag;
        $_alt = '';
        $full = '';
        $full_src = '';
        $srcset = '';
        $smallest_src = '';
		$new_32_src = '';
		$has_new_32_src = false;

        $w_attr = '';
        $h_attr = '';
        $css = '';

        // attribute id exists
        if( array_key_exists('attid', $el) ) {
            $attid = $el['attid'];
            $_alt = LTUtility::getAltTag($attid);

            $full = wp_get_attachment_image_src( $attid, 'full' );
            if( $full !== false && is_array($full) ) {
                $full_src = LTUtility::filterURL($full[0]);
                $w_attr = 'data-w="'.$full[1].'"';
                $h_attr = 'data-h="'.$full[2].'"';
                // Omit inline aspect-ratio when img uses phone AR stylesheet (aspect controlled by media queries)
                $css = ( !empty($el['_use_phone_ar_stylesheet']) ) ? '' : ('aspect-ratio:'.$full[1].'/'.$full[2].';');

                // just not going to use wordpress' wp_get_attachment_image_srcset at all, because it doesnt include super large sizes like 4096
                // $srcset = wp_get_attachment_image_srcset( $attid );
    
                // todo: test if image smaller than 265 width works
                $smallest = wp_get_attachment_image_src( $attid, '_265' );
                $smallest_src = $full_src;
                if( $smallest != false ) {
                    $smallest_src = LTUtility::filterURL($smallest[0]);
                }

				$new_32 = wp_get_attachment_image_src( $attid, '_32' );

				if( $new_32 != false ) {
					$new_32_src = LTUtility::filterURL($new_32[0]);
					$has_new_32_src = true;
				} else {
					$new_32_src = $smallest_src;
				}
            }
        }

        $css .= array_key_exists('sidebar_css', $el) ? $el['sidebar_css'] : '';
        // there is another freak bug where wp_get_attachment_image_srcset would not use my custom image sizes, but only some 150 and 768 square sizes http://leitform.ch/moschti, so I check if srcset contains _265, and if not create the custom srcset ( strpos($srcset, '265w') === false )
        // there was some freak bug with some website (http://a184.uk/), where wp_get_attachment_image_srcset would always return '' and i could not figure out why, so i do this instead of just else{
        if( $srcset == '' || $srcset == false || !array_key_exists('attid', $el) || strpos($srcset, '265w') === false ) {
            // $srcset might be set already, because im doing strpos($srcset, '265w') === false, so reset it
            $srcset = '';
            if( !array_key_exists('attid', $el) && array_key_exists('alt', $el) ) {
                $_alt = $el['alt'];
            }
            $full_src = LTUtility::getFullURL($el['sizes']['full']);
            $sizesToBeFilled = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];
            $sizes = $el['sizes'];

            foreach($sizes as $key => $value) {
                if( array_search($key, $sizesToBeFilled) !== false ){
                    $src = $value;
                    $src = LTUtility::getFullURL($src);
        
                    $srcset = $src.' '.substr($key, 1).'w, ' . $srcset;
                    $ix = array_search($key, $sizesToBeFilled);

                    if($ix !== false){
                        array_splice( $sizesToBeFilled, $ix, 1 );
                    }
                }
            }
            // fill rest of sizes with full image
            for($i=0; $i<count($sizesToBeFilled); $i++){
                $srcset .= $full_src.' '.substr($sizesToBeFilled[$i], 1).'w, ';
            }
            $srcset = substr( $srcset, 0, -2);
 
            $smallest = array_key_exists('_265', $sizes) ? $sizes['_265'] : false;
            if( $smallest == '' || $smallest == false ) {
                $smallest_src = $full_src;
            } else {
                $smallest_src = LTUtility::getFullURL($smallest);
            }
        }

        if( trim($_alt) != '' ) {
            $alt = $_alt;
        }
        $ar = $el['ar'];

        $isGif = false;
        $isGif = substr($full_src, -4) == '.gif' ? true : false;
        // used by imagehover addon
        $extra = array_key_exists('extra_attrs', $el) ? $el['extra_attrs'] : '';
        $extra_classes = array_key_exists('extra_classes', $el) ? $el['extra_classes'] : '';
        $small_src_before_lazyloading = 'data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==';
        if(LayFrontend_Options::$misc_options_image_loading_blur != 'standard') {
            $small_src_before_lazyloading = $smallest_src;
        }

		if(LayFrontend_Options::$misc_options_image_loading_blur == 'pixelated') {
			$small_src_before_lazyloading = $new_32_src;
			if($has_new_32_src) {
				$extra_classes .= ' has_32_src';
			}
		}

        switch( LayFrontend_Options::$image_loading ) {
            case 'instant_load':
                // note to myself: in "load_cpl_or_desktop_content_controller.js" the "instant_load" images are loaded
                if( $isGif ) {
                    return '<img class="lay-gif '.$extra_classes.'" src="'.$small_src_before_lazyloading.'" data-src="'.$full_src.'" data-ar="'.$ar.'" alt="'.$alt.'" '.$extra.' '.$w_attr.' '.$h_attr.' data-id="'.$attid.'" style="'.$css.'"/>';
                }
                if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                    return '<img class="lay-image-original '.$extra_classes.'" src="'.$small_src_before_lazyloading.'" data-src="'.$full_src.'" data-ar="'.$ar.'" alt="'.$alt.'" '.$extra.' '.$w_attr.' '.$h_attr.' data-id="'.$attid.'" style="'.$css.'"/>';
                }
                return '<img class="lay-image-responsive setsizes '.$extra_classes.'" src="'.$small_src_before_lazyloading.'" data-src="'.$smallest_src.'"  data-srcset="'.$srcset.'" sizes="" data-ar="'.$ar.'" alt="'.$alt.'" '.$extra.' '.$w_attr.' '.$h_attr.' data-id="'.$attid.'" style="'.$css.'"/>';
            break;
            case 'lazy_load':
                if( $isGif ) {
                    return '<img class="lay-gif lazyload '.$extra_classes.'" src="'.$small_src_before_lazyloading.'" data-src="'.$full_src.'" data-ar="'.$ar.'" alt="'.$alt.'" '.$extra.' '.$w_attr.' '.$h_attr.' data-id="'.$attid.'" style="'.$css.'"/>';
                }
                if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                    return '<img class="lay-image-original lazyload '.$extra_classes.'" src="'.$small_src_before_lazyloading.'" data-src="'.$full_src.'" data-ar="'.$ar.'" alt="'.$alt.'" '.$extra.' '.$w_attr.' '.$h_attr.' data-id="'.$attid.'" style="'.$css.'"/>';
                }
                return '<img class="lay-image-responsive lazyload '.$extra_classes.'" src="'.$small_src_before_lazyloading.'" data-src="'.$smallest_src.'"  data-srcset="'.$srcset.'" data-ar="'.$ar.'"  alt="'.$alt.'" '.$extra.' '.$w_attr.' '.$h_attr.' data-id="'.$attid.'" style="'.$css.'"/>';
            break;
        }
    }

    // this is basically the same function as above, getLazyImg, but it accepts an attid instead of a $el
    // todo: think about if i should create a function that outputs the <img markup. 
    // That function could be used by getLazyImgByImageId, getLazyImg and getMouseOverThumbImg
    public static function getLazyImgByImageId($attid, $size='', $extra_class=''){
        if( $attid == '' || $attid == false ) {
            return '';
        }
        
        // Check if attachment exists
        $attachment_exists = get_post($attid);
        if (!$attachment_exists) {
            error_log('getLazyImgByImageId: Attachment ID ' . $attid . ' does not exist in database');
            return '';
        }
        
        $alt = get_bloginfo('name');
        $_alt = '';
        $full = '';
        $full_src = '';
        $srcset = '';
        $smallest_src = '';
        $ar = '';

        $w_attr = '';
        $h_attr = '';

        // attribute id exists
        $_alt = LTUtility::getAltTag($attid);

        if( $_alt != '' ) {
            $alt = $_alt;
        }

        if( $size == '' ){
            $full = wp_get_attachment_image_src( $attid, 'full' );
            
            // Debug logging for problematic images
            if ($full === false) {
                error_log('getLazyImgByImageId: Cannot get full image for ID ' . $attid);
            }
            // todo: test if image smaller than 265 width works
            $smallest = wp_get_attachment_image_src( $attid, '_265' );
            $smallest_src = $full_src;
            if( $smallest != false ) {
                $smallest_src = LTUtility::filterURL($smallest[0]);
            }
            if(is_array($full)) {
                $full_src = LTUtility::filterURL($full[0]);
                // just not going to use wordpress' wp_get_attachment_image_srcset at all, because it doesnt include super large sizes like 4096
                // $srcset = wp_get_attachment_image_srcset( $attid );
                $w_attr = 'data-w="'.$full[1].'"';
                $h_attr = 'data-h="'.$full[2].'"';
                if($full[1] != 0) {
                    $ar = (int)$full[2] / (int)$full[1];
                }
            } else {
                error_log('getLazyImgByImageId: $full is not an array for ID ' . $attid . ' - Value: ' . print_r($full, true));
            }
        }
        else{
            // custom size is used! for example 'woocommerce_thumbnail'
            $img_of_size = wp_get_attachment_image_src( $attid, $size );
                if(is_array($img_of_size)) {
                $smallest_src = LTUtility::filterURL($img_of_size[0]);
                $full_src = LTUtility::filterURL($img_of_size[0]);
                // todo: is just the url of an image even a valid srcset?
                $srcset = $img_of_size[0];
                $w_attr = 'data-w="'.$img_of_size[1].'"';
                $h_attr = 'data-h="'.$img_of_size[2].'"';
                if($img_of_size[1] != 0) {
                    $ar = (int)$img_of_size[2] / (int)$img_of_size[1];
                }
            }
        }

        // there is another freak bug where wp_get_attachment_image_srcset would not use my custom image sizes, but only some 150 and 768 square sizes http://leitform.ch/moschti, so I check if srcset contains _265, and if not create the custom srcset ( strpos($srcset, '265w') === false )
        // there was some freak bug with some website (http://a184.uk/), where wp_get_attachment_image_srcset would always return '' and i could not figure out why, so i do this instead of just else{
        if( $srcset == '' || $srcset == false || strpos($srcset, '265w') === false ) {
            // $srcset might be set already, because im doing strpos($srcset, '265w') === false, so reset it
            $srcset = '';
            $sizesToBeFilled = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];

            foreach($sizesToBeFilled as $value) {
                $src = wp_get_attachment_image_url($attid, $value);
                if( $src === false ) {
                    $src = $full_src;
                } else {
                    $src = LTUtility::filterURL($src);
                }
                
                // Debug: Check if src is empty
                if (empty($src)) {
                    error_log('getLazyImgByImageId: Empty src for ID ' . $attid . ', size ' . $value . ', full_src: ' . $full_src);
                }
                
                $srcset = $src.' '.substr($value, 1).'w, ' . $srcset;
            }
            $srcset = substr($srcset, 0, -2);
            
            // Debug: Check final srcset
            if (empty(trim(str_replace(['w,', 'w'], '', $srcset)))) {
                error_log('getLazyImgByImageId: Empty srcset generated for ID ' . $attid . ' - srcset: ' . $srcset);
            }
    
            $smallest_src = wp_get_attachment_image_url($attid, '_265');
            if( $smallest_src === false ) {
                $smallest_src = $full_src;
            } else {
                $smallest_src = LTUtility::filterURL($smallest_src);
            }
        }

        $isGif = false;
        $isGif = substr($full_src, -4) == '.gif' ? true : false;

        $isSVG = false;
        $isSVG = substr($full_src, -4) == '.svg' ? true : false;

        switch( LayFrontend_Options::$image_loading ) {
            case 'instant_load':
                if( $isGif  ) {
                    return '<img class="lay-gif '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
                }
                if( $isSVG ) {
                    return '<img class="lay-svg '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
                }
                if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                    return '<img class="lay-image-original '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
                }
                return '<img class="lay-image-responsive setsizes '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw=="  data-srcset="'.$srcset.'" sizes="" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
            break;
            case 'lazy_load':
                if( $isGif ) {
                    return '<img class="lay-gif lazyload '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
                }
                if( $isSVG ) {
                    return '<img class="lay-svg lazyload '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
                }
                if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                    return '<img class="lay-image-original lazyload '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" data-id="'.$attid.'" alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
                }
                return '<img class="lay-image-responsive lazyload '.$extra_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw=="  data-srcset="'.$srcset.'" data-ar="'.$ar.'" data-id="'.$attid.'"  alt="'.$alt.'" '.$w_attr.' '.$h_attr.'/>';
            break;
        }
    }

    public static function getMouseOverThumbImg($el) {
        $isGif = false;
        // Todo: Same here as in getLazyImg
        $mo_attid = array_key_exists('mo_attid', $el) ? $el['mo_attid'] : false;
        $alt = get_bloginfo('name');
        $full = '';
        $full_src = '';
        $srcset = '';
        $smallest_src = '';
        $ar = 0;

        if( $mo_attid != false ) {
            $alt = LTUtility::getAltTag($mo_attid);

            $full = wp_get_attachment_image_src( $mo_attid, 'full' );
            $full_src = '';
            if( $full ) {
                $full_src = $full[0];
                $ar = (int)$el['mo_h'] / (int)$el['mo_w'];
            }
            $smallest = wp_get_attachment_image_src( $mo_attid, '_265' );
            $smallest_src = '';
            if( $smallest ) {
                $smallest_src = $smallest[0];
            }
            $srcset = wp_get_attachment_image_srcset( $mo_attid );

            // same fallback as getLazyImgByImageId: when WP returns empty
            // (some attachments / sub-sizes), build the srcset manually from
            // registered sizes, falling back to $full_src per size.
            if( $srcset == '' || $srcset == false || strpos($srcset, '265w') === false ) {
                $srcset = '';
                $sizesToBeFilled = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];
                foreach($sizesToBeFilled as $value) {
                    $src = wp_get_attachment_image_url($mo_attid, $value);
                    if( $src === false ) {
                        $src = $full_src;
                    } else {
                        $src = LTUtility::filterURL($src);
                    }
                    $srcset = $src.' '.substr($value, 1).'w, ' . $srcset;
                }
                $srcset = substr($srcset, 0, -2);
            }
        } else {
            // for backwards compatibility
            $sizes = $el['mo_sizes'];
            $full_src = LTUtility::getFullURL($el['mo_sizes']['full']);
            $sizesToBeFilled = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];
            $smallest_src = $sizes['_265'];

            if((int)$el['mo_w'] != 0){
                $ar = (int)$el['mo_h'] / (int)$el['mo_w'];
            }

            foreach($sizes as $key => $value) {
                if( array_search($key, $sizesToBeFilled) !== false ){
                    $src = $value;
                    $src = LTUtility::getFullURL($src);
        
                    $srcset = $src.' '.substr($key, 1).'w, ' . $srcset;
                    $ix = array_search($key, $sizesToBeFilled);

                    if($ix !== false){
                        array_splice( $sizesToBeFilled, $ix, 1 );
                    }
                }
            }
            // fill rest of sizes with full image
            for($i=0; $i<count($sizesToBeFilled); $i++){
                $srcset .= $full_src.' '.substr($sizesToBeFilled[$i], 1).'w, ';
            }
            $srcset = substr($srcset, 0, -2);
        }
        $isGif = substr($full_src, -4) == '.gif' ? true : false;

        $mo_w_attr = array_key_exists('mo_w', $el) ? 'data-w="'.$el['mo_w'].'"' : '';
        $mo_h_attr = array_key_exists('mo_h', $el) ? 'data-h="'.$el['mo_h'].'"' : '';

        // same as in getLazyImg, but using class "mo_thumb"
        switch( LayFrontend_Options::$image_loading ) {
            case 'instant_load':
                if( $isGif ) {
                    return '<img class="mo_thumb lay-gif" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" '.$mo_w_attr.' '.$mo_h_attr.' alt="'.$alt.'" data-id="'.$mo_attid.'"/>';
                }
                if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                    return '<img class="mo_thumb lay-image-original" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" '.$mo_w_attr.' '.$mo_h_attr.' alt="'.$alt.'" data-id="'.$mo_attid.'"/>';
                }
                return '<img class="mo_thumb lay-image-responsive setsizes" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw=="  data-srcset="'.$srcset.'" sizes="" data-ar="'.$ar.'" '.$mo_w_attr.' '.$mo_h_attr.' alt="'.$alt.'" data-id="'.$mo_attid.'"/>';
            break;
            case 'lazy_load':
                if( $isGif ) {
                    return '<img class="mo_thumb lay-gif lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" '.$mo_w_attr.' '.$mo_h_attr.' alt="'.$alt.'" data-id="'.$mo_attid.'"/>';
                }
                if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                    return '<img class="mo_thumb lay-image-original lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-src="'.$full_src.'" data-ar="'.$ar.'" '.$mo_w_attr.' '.$mo_h_attr.' alt="'.$alt.'" data-id="'.$mo_attid.'"/>';
                }
                return '<img class="mo_thumb lay-image-responsive lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw=="  data-srcset="'.$srcset.'" data-ar="'.$ar.'" '.$mo_w_attr.' '.$mo_h_attr.' alt="'.$alt.'" data-id="'.$mo_attid.'"/>';
            break;
        }
    }

    public static function stringifyCatIds($catids){
        if( $catids == '' || $catids == null  ) {
            return '[]';
        }
        if( is_string($catids) ) {
            return $catids;
        }
        // is a number like 3
        if( is_scalar($catids) ) {
            return '['.$catids.']';
        }
        if( is_array($catids) ) {
            for( $i=0; $i<count($catids); $i++ ) {
                $catids[$i] = (int)$catids[$i];
            }
        }
        return json_encode($catids);
    }

    public static function getCaptionMarkup($el){
        if( array_key_exists( 'caption', $el ) && $el['caption'] != '' ) {
            return '<div class="caption lay-textformat-parent">'.$el['caption'].'</div>'; 
        }
        return '';
    }

    /**
     * Text on Element overlay markup for img and html5video.
     * Outputs positioned text overlays with space (padding) support.
     * Uses inline styles for desktop space; when "Override on Phone" is enabled,
     * outputs a per-element style block (like img.php phone AR) to override at breakpoint.
     *
     * @param array $el Element with optional textOnElement
     * @param array $options Optional. May include 'grid_container_selector' for scoping the style block.
     * @return string HTML markup or empty string
     */
    /**
     * Convert hex color to rgba string (e.g. #000000 -> rgba(0,0,0,0.5)).
     *
     * @param string $hex Hex color e.g. #000000 or 000000
     * @param float  $alpha Alpha 0–1
     * @return string rgba(...)
     */
    private static function text_on_element_hex_to_rgba( $hex, $alpha = 0.5 ) {
        $hex = is_string( $hex ) ? ltrim( $hex, '#' ) : '';
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
            return 'rgba(0,0,0,' . (float) $alpha . ')';
        }
        $r = (int) hexdec( substr( $hex, 0, 2 ) );
        $g = (int) hexdec( substr( $hex, 2, 2 ) );
        $b = (int) hexdec( substr( $hex, 4, 2 ) );
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . (float) $alpha . ')';
    }

    /**
     * Clip gradient overlays (text-on-element, carousel slides) to parent element rounded corners.
     *
     * @param array  $el   Element with optional borderRadius (same keys as Gridder).
     * @param string $edge 'top' (gradient at top → round top-left & top-right) or 'bottom'.
     *
     * @return string Inline CSS fragment (append to style= then esc_attr the full attribute value).
     */
    public static function getGradientOverlayCornersCss( $el, $edge ) {
        return self::get_text_on_element_gradient_corners_css( $el, $edge );
    }

    /**
     * Clip text-on-element gradients to the same rounded corners as the image/video (top or bottom only).
     *
     * @param array  $el   Element with optional borderRadius (same keys as Gridder).
     * @param string $edge 'top' (gradient at top → round top-left & top-right) or 'bottom'.
     *
     * @return string Inline CSS fragment (no HTML escaping — append then esc_attr the full style).
     */
    private static function get_text_on_element_gradient_corners_css( $el, $edge ) {
        if ( ! array_key_exists( 'borderRadius', $el ) || ! is_array( $el['borderRadius'] ) ) {
            return '';
        }
        $br = $el['borderRadius'];
        if ( array_key_exists( 'enableBorderRadius', $br ) ) {
            $en = $br['enableBorderRadius'];
            if ( $en === false || $en === 0 || $en === '0' || $en === '' ) {
                return '';
            }
        }
        $append_shape = '';
        if ( array_key_exists( 'cornerShape', $br ) && $br['cornerShape'] !== '' && $br['cornerShape'] !== 'round' ) {
            $append_shape = 'corner-shape: ' . $br['cornerShape'] . ';';
        }
        if ( $edge === 'top' ) {
            $tl = array_key_exists( 'topLeft', $br ) ? (float) $br['topLeft'] : 0;
            $tr = array_key_exists( 'topRight', $br ) ? (float) $br['topRight'] : 0;
            if ( $tl <= 0 && $tr <= 0 ) {
                return '';
            }
            $tl_mu = isset( $br['topLeftMu'] ) ? $br['topLeftMu'] : 'px';
            $tr_mu = isset( $br['topRightMu'] ) ? $br['topRightMu'] : 'px';
            return 'border-top-left-radius:' . $tl . $tl_mu . ';border-top-right-radius:' . $tr . $tr_mu . ';border-bottom-left-radius:0;border-bottom-right-radius:0;' . $append_shape . 'overflow:hidden;';
        }
        if ( $edge === 'bottom' ) {
            $bl = array_key_exists( 'bottomLeft', $br ) ? (float) $br['bottomLeft'] : 0;
            $brg = array_key_exists( 'bottomRight', $br ) ? (float) $br['bottomRight'] : 0;
            if ( $bl <= 0 && $brg <= 0 ) {
                return '';
            }
            $bl_mu = isset( $br['bottomLeftMu'] ) ? $br['bottomLeftMu'] : 'px';
            $br_mu = isset( $br['bottomRightMu'] ) ? $br['bottomRightMu'] : 'px';
            return 'border-bottom-left-radius:' . $bl . $bl_mu . ';border-bottom-right-radius:' . $brg . $br_mu . ';border-top-left-radius:0;border-top-right-radius:0;' . $append_shape . 'overflow:hidden;';
        }
        return '';
    }

    private static function is_text_on_element_enabled( $t ) {
        return !( is_array( $t ) && array_key_exists( 'enabled', $t ) && $t['enabled'] === false );
    }

    public static function getTextOnElementGradientMarkup($el) {
        if ( ! array_key_exists( 'textOnElement', $el ) || ! is_array( $el['textOnElement'] ) ) {
            return '';
        }
        $t = $el['textOnElement'];
        if ( ! self::is_text_on_element_enabled( $t ) ) {
            return '';
        }
        $out = '';
        if ( ! empty( $t['darkGradientAtBottom'] ) ) {
            $color = isset( $t['darkGradientAtBottomColor'] ) && $t['darkGradientAtBottomColor'] !== '' ? $t['darkGradientAtBottomColor'] : '#000000';
            $rgba  = self::text_on_element_hex_to_rgba( $color, 0.5 );
            $corners = self::get_text_on_element_gradient_corners_css( $el, 'bottom' );
            $style   = 'position:absolute;bottom:0;left:0;right:0;height:30%;background:linear-gradient(to top, ' . $rgba . ', transparent);pointer-events:none;z-index:1;' . $corners;
            $out .= '<div class="lay-text-on-element-dark-gradient" style="' . esc_attr( $style ) . '"></div>';
        }
        if ( ! empty( $t['darkGradientAtTop'] ) ) {
            $color = isset( $t['darkGradientAtTopColor'] ) && $t['darkGradientAtTopColor'] !== '' ? $t['darkGradientAtTopColor'] : '#000000';
            $rgba  = self::text_on_element_hex_to_rgba( $color, 0.5 );
            $corners = self::get_text_on_element_gradient_corners_css( $el, 'top' );
            $style   = 'position:absolute;top:0;left:0;right:0;height:30%;background:linear-gradient(to bottom, ' . $rgba . ', transparent);pointer-events:none;z-index:1;' . $corners;
            $out .= '<div class="lay-text-on-element-dark-gradient-top" style="' . esc_attr( $style ) . '"></div>';
        }
        return $out;
    }

    public static function getTextOnElementOverlayMarkup($el, $options = array()) {
        if ( !array_key_exists('textOnElement', $el) || !is_array($el['textOnElement']) ) {
            return '';
        }
        $t = $el['textOnElement'];
        if ( ! self::is_text_on_element_enabled( $t ) ) {
            return '';
        }
        $positions = array('topLeft','topCenter','topRight','leftCenter','centerCenter','rightCenter','bottomLeft','bottomCenter','bottomRight');
        $has_any = false;
        foreach ( $positions as $pos ) {
            if ( array_key_exists($pos, $t) && $t[$pos] !== '' && trim($t[$pos]) !== '' ) {
                $has_any = true;
                break;
            }
        }
        if ( !$has_any ) {
            return '';
        }

        // Desktop space values (always used for inline styles), default 10px
        $lr = isset($t['spaceLeftRight']) && is_numeric($t['spaceLeftRight']) ? (float)$t['spaceLeftRight'] : 10;
        $tb = isset($t['spaceTopBottom']) && is_numeric($t['spaceTopBottom']) ? (float)$t['spaceTopBottom'] : 10;
        $lr_mu = isset($t['spaceLeftRightMu']) && $t['spaceLeftRightMu'] === '%' ? '%' : 'px';
        $tb_mu = isset($t['spaceTopBottomMu']) && $t['spaceTopBottomMu'] === '%' ? '%' : 'px';
        $lr_val = $lr_mu === '%' ? $lr . '%' : $lr . 'px';
        $tb_val = $tb_mu === '%' ? $tb . '%' : $tb . 'px';

        // Vertical offset for left/center/right center (default -10px)
        $vo_center = isset( $t['verticalOffsetCenter'] ) && is_numeric( $t['verticalOffsetCenter'] ) ? (float) $t['verticalOffsetCenter'] : 0;
        $vo_center_mu = ! empty( $t['verticalOffsetCenterMu'] ) && $t['verticalOffsetCenterMu'] === '%' ? '%' : 'px';
        $vo_center_val = $vo_center_mu === '%' ? $vo_center . '%' : $vo_center . 'px';

        $pos_padding = array(
            'topLeft'     => 'padding-top:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'topCenter'   => 'padding-top:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'topRight'    => 'padding-top:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'leftCenter'  => 'padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'centerCenter'=> 'padding:'.$tb_val.' '.$lr_val.';',
            'rightCenter' => 'padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'bottomLeft'  => 'padding-bottom:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'bottomCenter'=> 'padding-bottom:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
            'bottomRight' => 'padding-bottom:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';',
        );

        $pos_styles = array(
            'topLeft'     => 'top:0;left:0;',
            'topCenter'   => 'top:0;left:50%;transform:translateX(-50%);',
            'topRight'    => 'top:0;right:0;',
            'leftCenter'  => 'top:calc(50% + ' . $vo_center_val . ');left:0;transform:translateY(-50%);',
            'centerCenter'=> 'top:calc(50% + ' . $vo_center_val . ');left:50%;transform:translate(-50%,-50%);',
            'rightCenter' => 'top:calc(50% + ' . $vo_center_val . ');right:0;transform:translateY(-50%);',
            'bottomLeft'  => 'bottom:0;left:0;',
            'bottomCenter'=> 'bottom:0;left:50%;transform:translateX(-50%);',
            'bottomRight' => 'bottom:0;right:0;',
        );

        $text_format_class = isset(LayFrontend_Options::$textFormatParentClass) ? LayFrontend_Options::$textFormatParentClass : 'lay-textformat-parent';
        $grid_container_selector = isset($options['grid_container_selector']) ? $options['grid_container_selector'] : '';
        // Use options['relid'] when provided (e.g. carousel slides: slide has no relid, caller passes carousel relid)
        $relid = isset( $options['relid'] ) ? (int) $options['relid'] : ( isset( $el['relid'] ) ? (int) $el['relid'] : 0 );
        // Build a scoped per-position selector. Concatenating ` .lay-text-on-element-<pos>`
        // onto a comma-joined base would only attach to the last root; we apply
        // the full per-position suffix to every root via grid_container_scoped_selectors.
        $build_pos_sel = function( $pos_name ) use ( $options, $grid_container_selector, $relid ) {
            if ( isset( $options['override_selector'] ) ) {
                return $options['override_selector'] . ' .lay-text-on-element-' . $pos_name;
            }
            $full = ' .col.id-' . $relid . ' .lay-text-on-element-overlay .lay-text-on-element-' . $pos_name;
            return $grid_container_selector !== ''
                ? Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_container_selector, $full )
                : $full;
        };

        $out = '<div class="lay-text-on-element-overlay">';
        foreach ( $positions as $pos ) {
            $html = isset($t[$pos]) ? trim($t[$pos]) : '';
            if ( $html === '' ) {
                continue;
            }
            $building_raw_multilingual_layout_cache = class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache();
            if ( !$building_raw_multilingual_layout_cache && class_exists('Setup') ) {
                $html = Setup::apply_qtranslate_with_fallback($html);
            }
            if ( !$building_raw_multilingual_layout_cache ) {
                $html = apply_filters('the_content', $html);
            }
            $padding = $pos_padding[$pos];
            $style = 'position:absolute;z-index:2;overflow:hidden;box-sizing:border-box;line-height:1.3;pointer-events:none;' . $pos_styles[$pos] . ( !empty($t['enableSpacePhone']) ? '' : $padding );
            $out .= '<div class="lay-text-on-element-position lay-text-on-element-'.$pos.' '.esc_attr($text_format_class).'" style="'.esc_attr($style).'">'.$html.'</div>';
        }
        $out .= '</div>';

        // When "Override on Phone" is enabled, output style block with desktop + phone padding on position divs
        if ( !empty($t['enableSpacePhone']) ) {
            $lr_ph = isset($t['spaceLeftRightPhone']) && is_numeric($t['spaceLeftRightPhone']) ? (float)$t['spaceLeftRightPhone'] : 10;
            $tb_ph = isset($t['spaceTopBottomPhone']) && is_numeric($t['spaceTopBottomPhone']) ? (float)$t['spaceTopBottomPhone'] : 10;
            $lr_mu_ph = isset($t['spaceLeftRightPhoneMu']) && $t['spaceLeftRightPhoneMu'] === '%' ? '%' : 'px';
            $tb_mu_ph = isset($t['spaceTopBottomPhoneMu']) && $t['spaceTopBottomPhoneMu'] === '%' ? '%' : 'px';
            $lr_val_ph = $lr_mu_ph === '%' ? $lr_ph . '%' : $lr_ph . 'px';
            $tb_val_ph = $tb_mu_ph === '%' ? $tb_ph . '%' : $tb_ph . 'px';
            $vo_center_ph = isset( $t['verticalOffsetCenterPhone'] ) && is_numeric( $t['verticalOffsetCenterPhone'] ) ? (float) $t['verticalOffsetCenterPhone'] : 0;
            $vo_center_mu_ph = ! empty( $t['verticalOffsetCenterPhoneMu'] ) && $t['verticalOffsetCenterPhoneMu'] === '%' ? '%' : 'px';
            $vo_center_val_ph = $vo_center_mu_ph === '%' ? $vo_center_ph . '%' : $vo_center_ph . 'px';
            $bp = isset(LayFrontend_Options::$breakpoint) ? (int) LayFrontend_Options::$breakpoint : (int) get_option('lay_breakpoint', 600);
            $css = $build_pos_sel( 'topLeft' ).'{padding-top:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'topCenter' ).'{padding-top:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'topRight' ).'{padding-top:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'leftCenter' ).'{padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'centerCenter' ).'{padding:'.$tb_val.' '.$lr_val.';}';
            $css .= $build_pos_sel( 'rightCenter' ).'{padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'bottomLeft' ).'{padding-bottom:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'bottomCenter' ).'{padding-bottom:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= $build_pos_sel( 'bottomRight' ).'{padding-bottom:'.$tb_val.';padding-left:'.$lr_val.';padding-right:'.$lr_val.';}';
            $css .= '@media (max-width:'.$bp.'px){';
            $css .= $build_pos_sel( 'topLeft' ).'{padding-top:'.$tb_val_ph.';padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'topCenter' ).'{padding-top:'.$tb_val_ph.';padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'topRight' ).'{padding-top:'.$tb_val_ph.';padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'leftCenter' ).'{padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'centerCenter' ).'{padding:'.$tb_val_ph.' '.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'rightCenter' ).'{padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'leftCenter' ).'{top:calc(50% + '.$vo_center_val_ph.');}';
            $css .= $build_pos_sel( 'centerCenter' ).'{top:calc(50% + '.$vo_center_val_ph.');}';
            $css .= $build_pos_sel( 'rightCenter' ).'{top:calc(50% + '.$vo_center_val_ph.');}';
            $css .= $build_pos_sel( 'bottomLeft' ).'{padding-bottom:'.$tb_val_ph.';padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'bottomCenter' ).'{padding-bottom:'.$tb_val_ph.';padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}';
            $css .= $build_pos_sel( 'bottomRight' ).'{padding-bottom:'.$tb_val_ph.';padding-left:'.$lr_val_ph.';padding-right:'.$lr_val_ph.';}}';
            $out = '<style>'.$css.'</style>' . $out;
        }

        return $out;
    }

}
new LayElFunctions();
