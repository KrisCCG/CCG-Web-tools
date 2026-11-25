<?php
/**
 * Scroll Transitions CSS Output
 * 
 * Generates CSS for scroll transition animations (transform, scale, blur, opacity)
 * Only outputs CSS when scroll transitions are enabled in Lay Options
 */

class CSS_Output_Scroll_Transitions {

    public function __construct(){
        add_action( 'wp_enqueue_scripts', array( $this, 'lay_customize_css' ), 20 );
    }

    public static function lay_customize_css(){
        // Only output CSS if scroll transitions are enabled
        $misc_options_element_transition_on_scroll = get_option( 'misc_options_element_transition_on_scroll', '' );
        if($misc_options_element_transition_on_scroll != 'on') {
            return; // Exit early if disabled
        }

        // $sharedStyles apply to desktop, tablet and mobile
        $sharedStyles = "";
        // $desktopAndTabletStyles applies to desktop and tablet size
        $desktopAndTabletStyles = "";
        $mobileStyles = "";

        // Get scroll transition settings
        $misc_options_scroll_transition_duration = get_option( 'misc_options_scroll_transition_duration', 300 );
        if($misc_options_scroll_transition_duration == ''){
            $misc_options_scroll_transition_duration = 300;
        }
        $misc_options_scroll_transition_easing = get_option( 'misc_options_scroll_transition_easing', 'ease' );
        if($misc_options_scroll_transition_easing == ''){
            $misc_options_scroll_transition_easing = 'ease';
        }
        $misc_options_scroll_transition_offset_desktop = get_option( 'misc_options_scroll_transition_offset_desktop', 90 );
        if($misc_options_scroll_transition_offset_desktop == ''){
            $misc_options_scroll_transition_offset_desktop = 90;
        }
        $misc_options_scroll_transition_offset_mobile = get_option( 'misc_options_scroll_transition_offset_mobile', 40 );
        if($misc_options_scroll_transition_offset_mobile == ''){
            $misc_options_scroll_transition_offset_mobile = 40;
        }

        // Check if scale animation is enabled
        $misc_options_scroll_transition_animate_scale = get_option('misc_options_scroll_transition_animate_scale', '');
        $misc_options_scroll_transition_scale_start = get_option('misc_options_scroll_transition_scale_start', '0.95');
        if($misc_options_scroll_transition_scale_start == ''){
            $misc_options_scroll_transition_scale_start = 0.95;
        }

        // Check if blur animation is enabled
        $misc_options_scroll_transition_animate_blur = get_option('misc_options_scroll_transition_animate_blur', '');
        $misc_options_scroll_transition_blur_start = get_option('misc_options_scroll_transition_blur_start', '10');
        if($misc_options_scroll_transition_blur_start == ''){
            $misc_options_scroll_transition_blur_start = 10;
        }

        // Build transition properties separately so transition-delay can be set by JavaScript
        // transition-delay is intentionally omitted so JavaScript can set it dynamically
        $transition_properties_list = 'transform, opacity';
        if($misc_options_scroll_transition_animate_blur == 'on'){
            $transition_properties_list .= ', filter';
        }

        // The :not(.disable-css-transitions) guard prevents this rule from
        // overriding navigation_animations_controller's disable-css-transitions
        // class. Without it, both rules have the same specificity (single
        // class) AND both are !important, and since this customizer CSS loads
        // AFTER the SCSS-compiled .disable-css-transitions { transition: none
        // !important } rule, this transition wins by source-order. The result
        // was that during a navigation in-animation on a col that already had
        // .in-view (e.g. fp-section first slide), the JS opacity tween from
        // anime.js was being smoothed by a 300ms CSS transition — visually
        // appearing as fade-out (0→...) then fade-in, the "in/out/in" flicker
        // reported on /de/fs-slider/. With the guard, .disable-css-transitions
        // wins during the nav animation, JS animation runs cleanly, and the
        // smooth scroll-trigger transition resumes once the nav animation
        // completes (and removes the disable-css-transitions class).
        $sharedStyles .= '.in-view:not(.disable-css-transitions),
        .col.type-text.in-view:not(.disable-css-transitions) .text{
            transition-property: '.$transition_properties_list.' !important;
            transition-duration: '.$misc_options_scroll_transition_duration.'ms !important;
            transition-timing-function: '.$misc_options_scroll_transition_easing.' !important;
        }';

        // Build transform for .below state (desktop/tablet)
        $below_transform_desktop = 'translateY('.$misc_options_scroll_transition_offset_desktop.'px)';
        if($misc_options_scroll_transition_animate_scale == 'on'){
            $below_transform_desktop = 'translateY('.$misc_options_scroll_transition_offset_desktop.'px) scale('.$misc_options_scroll_transition_scale_start.')';
        }

        // Build filter for .below state
        $below_filter = '';
        if($misc_options_scroll_transition_animate_blur == 'on'){
            $below_filter = 'filter: blur('.$misc_options_scroll_transition_blur_start.'px);';
        }

        // Build transform for .below state (mobile)
        $below_transform_mobile = 'translateY('.$misc_options_scroll_transition_offset_mobile.'px)';
        if($misc_options_scroll_transition_animate_scale == 'on'){
            $below_transform_mobile = 'translateY('.$misc_options_scroll_transition_offset_mobile.'px) scale('.$misc_options_scroll_transition_scale_start.')';
        }

        // Build transform for .in-view state (end state)
        // Only include scale(1) if scale animation is enabled
        // Only include filter: blur(0) if blur animation is enabled
        $in_view_transform = 'translateY(0)';
        if($misc_options_scroll_transition_animate_scale == 'on'){
            $in_view_transform = 'translateY(0) scale(1)';
        }

        $in_view_filter = '';
        if($misc_options_scroll_transition_animate_blur == 'on'){
            $in_view_filter = 'filter: blur(0);';
        }

        // Desktop & Tablet: .below state
        $desktopAndTabletStyles .= '.col.type-socialmediaicons.below,
        .col.type-project.below,
        .col.type-embed.below,
        .col.type-carousel.below,
        .col.type-img.below,
        .col.type-marquee.below,
        .col.type-html5video.below,
        .col.type-video.below,
        .col.type-hr.below,
        .col.type-table.below,
        .col.type-textfooter.below,
        .col.type-projectindex.below,
        .col.type-productindex.below,
        .col.type-productsgrid li.below,
        .lay-thumbnailgrid-filter-wrap.below,
        .col.type-thumbnailgrid .thumbnail-wrap .below,
        .col.type-elementgrid .element-wrap.below,
        .show-news.below,
        .col.stack-has-border-or-shadow-or-rounded-corners.below {
            transform: '.$below_transform_desktop.';
            '.$below_filter.'
        }
        .col.type-text.below {
            transform: '.$below_transform_desktop.';
            '.$below_filter.'
        }';

        // Desktop & Tablet: .in-view state
        $desktopAndTabletStyles .= '.col.type-socialmediaicons.in-view,
        .col.type-project.in-view,
        .col.type-embed.in-view,
        .col.type-carousel.in-view,
        .col.type-img.in-view,
        .col.type-marquee.in-view,
        .col.type-html5video.in-view,
        .col.type-video.in-view,
        .col.type-table.in-view,
        .col.type-projectindex.in-view,
        .col.type-productsgrid .lay-productsgrid-filter.in-view,
        .col.type-productsgrid .lay-productsgrid-tagfilter.in-view,
        .col.type-productsgrid li.in-view,
        .lay-thumbnailgrid-filter-wrap.in-view,
        .col.type-thumbnailgrid .thumbnail-wrap .in-view,
        .col.type-elementgrid .element-wrap.in-view {
            transform: '.$in_view_transform.';
            '.$in_view_filter.'
        }
        .col.type-text.in-view {
            transform: '.$in_view_transform.';
            '.$in_view_filter.'
        }';

        // Mobile: .below state
        $mobileStyles .= '.col.type-socialmediaicons.below,
        .col.type-project.below,
        .col.type-embed.below,
        .col.type-carousel.below,
        .col.type-img.below,
        .col.type-marquee.below,
        .col.type-html5video.below,
        .col.type-video.below,
        .col.type-hr.below,
        .col.type-table.below,
        .col.type-textfooter.below,
        .col.type-projectindex.below,
        .col.type-productindex.below,
        .col.type-productsgrid li.below,
        .lay-thumbnailgrid-filter-wrap.below,
        .col.type-thumbnailgrid .thumbnail-wrap .below,
        .col.type-elementgrid .element-wrap.below,
        .show-news.below,
        .col.stack-has-border-or-shadow-or-rounded-corners.below {
            transform: '.$below_transform_mobile.';
            '.$below_filter.'
        }
        .col.type-text.below {
            transform: '.$below_transform_mobile.';
            '.$below_filter.'
        }';

        // Mobile: .in-view state
        $mobileStyles .= '.col.type-socialmediaicons.in-view,
        .col.type-project.in-view,
        .col.type-embed.in-view,
        .col.type-carousel.in-view,
        .col.type-img.in-view,
        .col.type-marquee.in-view,
        .col.type-html5video.in-view,
        .col.type-video.in-view,
        .col.type-table.in-view,
        .col.type-projectindex.in-view,
        .col.type-productsgrid .lay-productsgrid-filter.in-view,
        .col.type-productsgrid .lay-productsgrid-tagfilter.in-view,
        .col.type-productsgrid li.in-view,
        .lay-thumbnailgrid-filter-wrap.in-view,
        .col.type-thumbnailgrid .thumbnail-wrap .in-view,
        .col.type-elementgrid .element-wrap.in-view {
            transform: '.$in_view_transform.';
            '.$in_view_filter.'
        }
        .col.type-text.in-view {
            transform: '.$in_view_transform.';
            '.$in_view_filter.'
        }';

        // Mobile: Auto phone layout one-col rows .below state
        $mobileStyles .= '
        .nocustomphonegrid .row.one-col-row .col.type-socialmediaicons.below,
        .nocustomphonegrid .row.one-col-row .col.type-textfooter.below,
        .nocustomphonegrid .row.one-col-row .col.type-hr.below,
        .nocustomphonegrid .row.one-col-row .col.type-table.below,
        .nocustomphonegrid .row.one-col-row .col.type-project.below,
        .nocustomphonegrid .row.one-col-row .col.type-embed.below,
        .nocustomphonegrid .row.one-col-row .col.type-carousel.below,
        .nocustomphonegrid .row.one-col-row .col.type-img.below,
        .nocustomphonegrid .row.one-col-row .col.type-marquee.below,
        .nocustomphonegrid .row.one-col-row .col.type-html5video.below,
        .nocustomphonegrid .row.one-col-row .col.type-video.below,
        .nocustomphonegrid .row.one-col-row .col.type-projectindex.below,
        .nocustomphonegrid .row.one-col-row .col.type-productindex.below,
        .nocustomphonegrid .row.one-col-row .col.stack-has-border-or-shadow-or-rounded-corners.below {
            transform: '.$below_transform_mobile.';
            '.$below_filter.'
        }
        .nocustomphonegrid .row.one-col-row .col.type-text.below {
            transform: '.$below_transform_mobile.';
            '.$below_filter.'
        }';

        // Mobile: Auto phone layout one-col rows .in-view state
        $mobileStyles .= '
        .nocustomphonegrid .row.one-col-row .col.type-socialmediaicons.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-textfooter.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-hr.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-table.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-project.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-embed.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-carousel.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-img.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-marquee.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-html5video.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-video.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-projectindex.in-view,
        .nocustomphonegrid .row.one-col-row .col.type-productindex.in-view,
        .nocustomphonegrid .row.one-col-row .col.stack-has-border-or-shadow-or-rounded-corners.in-view {
            transform: '.$in_view_transform.';
            '.$in_view_filter.'
        }
        .nocustomphonegrid .row.one-col-row .col.type-text.in-view {
            transform: '.$in_view_transform.';
            '.$in_view_filter.'
        }';

        // Opacity animation (if enabled)
        $misc_options_element_transition_animate_opacity = get_option('misc_options_element_transition_animate_opacity', 'on');
        if($misc_options_element_transition_animate_opacity == 'on') {
            // needs to be !important because div can have opacity style attribute because of new gidder opacity setting
            $sharedStyles .= '.below {
                opacity: 0!important;
            }
            .in-view,
            .col.type-text.in-view .text {
                opacity: 1;
            }';
        }

        // Output the CSS with dynamic breakpoint
        $breakpoint = get_option('lay_breakpoint', 600);
        $breakpoint = (int)$breakpoint;

        wp_add_inline_style( 'frontend-style',
        '/* scroll transitions css */
            '.$sharedStyles.'
            /* desktop and tablet */
            @media (min-width: '.($breakpoint+1).'px){'
                .$desktopAndTabletStyles.
            '}
            @media (max-width: '.($breakpoint).'px){'
                .$mobileStyles.
            '}'
        );
    }
}

new CSS_Output_Scroll_Transitions();

