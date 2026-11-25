<?php
class LayHorizontalScroll {

    private static $optionsLoaded = false;
    private static $featureEnabled = false;
    private static $activeInProjects = 'all';
    private static $activeInPages = 'off';
    private static $activeInCategories = 'off';
    private static $individualProjectIds = array();
    private static $individualPageIds = array();
    private static $individualCategoryIds = array();
    private static $currentActive = false;

    public function __construct(){
        add_action( 'init', array( __CLASS__, 'ensure_options' ) );
        add_action( 'wp', array( $this, 'determine_current_state' ) );
        add_filter( 'body_class', array( $this, 'add_body_class' ) );
        add_filter( 'language_attributes', array( $this, 'filter_language_attributes' ), 10, 2 );
    }

    public static function ensure_options(){
        if( self::$optionsLoaded ){
            return;
        }

        self::$featureEnabled = get_option( 'misc_options_enable_horizontal_scroll', '' ) == 'on' && !MiscOptions::$locked;
        // Empty DB values must not fall through evaluate_entity() default (inactive). Inline so partial theme copies cannot miss a helper method.
        $scope_projects = get_option( 'horizontal_scroll_active_in_projects', 'all' );
        self::$activeInProjects = ( $scope_projects === '' || $scope_projects === null || $scope_projects === false ) ? 'all' : $scope_projects;
        $scope_pages = get_option( 'horizontal_scroll_active_in_pages', 'all' );
        self::$activeInPages = ( $scope_pages === '' || $scope_pages === null || $scope_pages === false ) ? 'all' : $scope_pages;
        $scope_categories = get_option( 'horizontal_scroll_active_in_categories', 'off' );
        self::$activeInCategories = ( $scope_categories === '' || $scope_categories === null || $scope_categories === false ) ? 'off' : $scope_categories;
        self::$individualProjectIds = self::normalize_ids( get_option( 'horizontal_scroll_individual_project_ids', '' ) );
        self::$individualPageIds = self::normalize_ids( get_option( 'horizontal_scroll_individual_page_ids', '' ) );
        self::$individualCategoryIds = self::normalize_ids( get_option( 'horizontal_scroll_individual_category_ids', '' ) );

        self::$optionsLoaded = true;
    }

    private static function normalize_ids( $value ){
        if( $value === '' || $value === null ){
            return array();
        }
        if( is_string( $value ) ){
            $decoded = json_decode( $value, true );
            if( json_last_error() === JSON_ERROR_NONE ){
                $value = $decoded;
            }
        }
        if( ! is_array( $value ) ){
            return array();
        }
        $ids = array();
        foreach( $value as $id ){
            $id = (int)$id;
            if( $id > 0 ){
                $ids[] = $id;
            }
        }
        if( empty( $ids ) ){
            return array();
        }
        $ids = array_unique( $ids );
        return array_values( $ids );
    }

    public function determine_current_state(){
        self::ensure_options();
        self::$currentActive = false;

        if( ! self::$featureEnabled ){
            return;
        }

        $data = Frontend::get_current_type_id_slug_catid();
        if( ! is_array( $data ) ){
            return;
        }

        $type = isset( $data['type'] ) ? $data['type'] : '';
        $id = isset( $data['id'] ) ? (int)$data['id'] : 0;

        if( $type === '' ){
            return;
        }

        self::$currentActive = self::should_activate( $id, $type, false );
    }

    public function add_body_class( $classes ){
        return $classes;
    }

    public static function is_currently_active(){
        self::ensure_options();
        return self::$currentActive;
    }

    public function filter_language_attributes( $output, $doctype = 'html' ){
        if( ! self::$currentActive ){
            return $output;
        }

        if( strpos( $output, 'class=' ) === false ){
            $output .= ' class="horizontal-scroll-active"';
            return $output;
        }

        $output = preg_replace( '/class="([^"]*)"/', 'class="$1 horizontal-scroll-active"', $output, 1 );
        return $output;
    }

    public static function should_activate( $id, $type, $is_project_overlay = false ){
        self::ensure_options();

        if( ! self::$featureEnabled ){
            return false;
        }

        $type = strtolower( $type );
        $id = (int)$id;

        if( self::scrollslider_conflicts( $id, $type ) || self::fullscreen_conflicts( $id, $type ) || self::cover_conflicts( $id, $type ) ){
            return false;
        }

        switch( $type ){
            case 'project':
            case 'post':
                return self::evaluate_entity( $id, self::$activeInProjects, self::$individualProjectIds );
            case 'page':
                return self::evaluate_entity( $id, self::$activeInPages, self::$individualPageIds );
            case 'category':
                return self::evaluate_entity( $id, self::$activeInCategories, self::$individualCategoryIds );
            default:
                return false;
        }
    }

    private static function evaluate_entity( $id, $setting, $individual_ids ){
        if( $id <= 0 ){
            return false;
        }
        $setting = $setting === null ? '' : $setting;
        switch( $setting ){
            case 'all':
            case 'on':
                return true;
            case 'individual':
                return in_array( $id, $individual_ids, true );
            default:
                return false;
        }
    }

    private static function scrollslider_conflicts( $id, $type ){
        return class_exists( 'LayThemeScrollSlider' )
            && method_exists( 'LayThemeScrollSlider', 'isActiveHere' )
            && LayThemeScrollSlider::isActiveHere( $id, $type );
    }

    private static function fullscreen_conflicts( $id, $type ){
        return class_exists( 'LayThemeMagneticSlides' )
            && method_exists( 'LayThemeMagneticSlides', 'isActiveHere' )
            && LayThemeMagneticSlides::isActiveHere( $id, $type );
    }

    private static function cover_conflicts( $id, $type ){
        // Use hasCover(), not hasCoverForReal(): the latter ignores misc_options_cover (master Cover off).
        return class_exists( 'LayCoverFunctions' )
            && method_exists( 'LayCoverFunctions', 'hasCover' )
            && LayCoverFunctions::hasCover( $type, $id );
    }

}
new LayHorizontalScroll();

