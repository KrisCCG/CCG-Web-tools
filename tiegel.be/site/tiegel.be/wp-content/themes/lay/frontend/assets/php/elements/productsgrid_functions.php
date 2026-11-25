<?php

class LayProductsgridFunctions{
    public function __construct(){
        add_action('wp_ajax_get_productsgrid_filter_markup', array($this, 'get_productsgrid_filter_markup_ajax'));
        add_action('wp_ajax_get_productsgrid_markup', array($this, 'get_productsgrid_markup_ajax'));
    }

    private static function get_term_name_for_qtranslate($term, $taxonomy){
        $name = isset($term->name) ? $term->name : '';
        if ( class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache() && isset($term->term_id) ) {
            $raw_name = get_term_field('name', $term->term_id, $taxonomy, 'raw');
            if ( ! is_wp_error($raw_name) && $raw_name !== '' ) {
                $name = $raw_name;
            }
        }
        return Setup::apply_qtranslate($name);
    }

    public static function get_productsgrid_markup_ajax(){
        $config = $_POST['config'];
        $markup = LayProductsgridFunctions::build_lay_products_markup($config, true);
        echo $markup;
        die();
    }

    public static function get_productsgrid_filter_markup_ajax(){
        $config = $_POST['config'];
        echo LayProductsgridFunctions::lay_get_productsgrid_filter_markup($config);
        die();
    }


    // products thumbnails grid
    public static function build_lay_products_markup_with_filter($config){
        $markup = LayProductsgridFunctions::lay_get_productsgrid_filter_markup($config);
        $products_markup = LayProductsgridFunctions::build_lay_products_markup($config);
        $markup .= $products_markup;
        return $markup;
    }

    // products thumbnails grid

    public static function build_lay_products_markup($config, $forGridder = false){


        // TODO: do we need the desktop, tablet, phone configuration here in switch statement?
        $keysToIgnore = array('desktop', 'tablet', 'phone', 'sortedTagFilters', 'sortedFilters', 'id', 'showfilter', 'showtagfilter', 'tagFilterBehaviour', 'limitThumbnails', 'deselect_tags', 'reorder_tags', 'layoutType', 'default_filter_category', 'showMoreButton', 'showMoreButtonStyle', 'moreButtonText', 'moreButtonAlignment', 'moreButtonSpaceTop');

        // if this markup is for the gridder, we only need to return the products for: either chosen category or if category filter is active, chosen default filter category
        if($forGridder){
            if( array_key_exists('showfilter', $config) && ($config['showfilter'] === 'true' || $config['showfilter'] === true) ) {
                if( array_key_exists('default_filter_category', $config) && $config['default_filter_category'] != '' ) {
                    $defaultCat = $config['default_filter_category'];
                    $config['category'] = array( array('id' => $defaultCat));
                }
            }else{
                if( array_key_exists('category', $config) && is_array($config['category']) && count($config['category']) == 0 || !array_key_exists('category', $config) || $config['category'] == '' ) {
                    // if category is empty, get all categories. if i dont do this, orderby doesnt work
                    $product_category_ids = array();
                    $categories = get_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'menu_order' ) );
                    foreach( $categories as $category ) {
                        $product_category_ids []= array('id' => $category->term_id);
                    }
                    $config['category'] = $product_category_ids;
                }
            }
        } else {
            // if the markup is not for the gridder, but for the frontend, we need to get all products if a filter is enabled
            // because the filtering is then done through javascript
            // so, for "category", we add all available category ids, if i dont do this, orderby doesnt work
            if( array_key_exists('showfilter', $config) && ($config['showfilter'] === 'true' || $config['showfilter'] === true) ) {
                $product_category_ids = array();
                $categories = get_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'menu_order' ) );
                foreach( $categories as $category ) {
                    $product_category_ids []= array('id' => $category->term_id);
                }
                $config['category'] = $product_category_ids;
            }
            if( array_key_exists('showtagfilter', $config) && ($config['showtagfilter'] === 'true' || $config['showtagfilter'] === true) ) {
                $config['tag'] = '';
            }
        }

        

        // create the woocommerce shortcode
        $shortcode = '[products ';
        foreach( $config as $key => $value ) {

            if( in_array($key, $keysToIgnore) ) {
                continue;
            }

            switch( $key ) {
                case 'category':
                    if( is_array($value) ) {
                        $cat_array = array();
                        foreach( $value as $key2 => $value2 ) {
                            if(array_key_exists('id', $value2)) {
                                $cat_array []= $value2['id'];
                            } else {
                                $cat_array []= $value2['value'];
                            }
                        }
                        $imploded_array = implode(',', $cat_array);
                        $shortcode .= $key.'="'.$imploded_array.'" ';
                    }
                break;
                case 'tag':
                    if( is_array($value) ) {
                        $cat_array = array();
                        foreach( $value as $key2 => $value2 ) {
                            // for tags it seems we need to use the slug, not the id
                            $cat_array []= $value2['value'];
                        }
                        $imploded_array = implode(',', $cat_array);
                        $shortcode .= $key.'="'.$imploded_array.'" ';
                    }
                break;
                break;
                default:
                    if(!is_array($value) && !is_array($key)){
                        if($value == '' && $key != 'skus'){
                            $value = 'false';
                        }
                        $shortcode .= $key.'="'.$value.'" ';
                    }
                break;
            }
        }

        $shortcode = substr($shortcode, 0, -1);
        $shortcode .= ']';

        // create shortcode markup
        $markup = do_shortcode( $shortcode );

        $markup = str_replace('<ul class="products', '<ul class="lay-products', $markup);
        $markup = str_replace('<li class="product', '<li class="lay-product', $markup);

        // If this is for the gridder and Load More is enabled, limit the number of products shown
        if ($forGridder) {
            $showMoreButton = ( array_key_exists('showMoreButton', $config) && ($config['showMoreButton'] === true || $config['showMoreButton'] === 'true') ) ? true : false;
            if ($showMoreButton) {
                // Get rowsToShow for desktop (default for gridder preview)
                $rowsToShow = 0;
                if ( array_key_exists('desktop', $config) && array_key_exists('rowsToShow', $config['desktop']) ) {
                    $rowsToShow = intval($config['desktop']['rowsToShow']);
                }
                // Get colCount for desktop
                $colCount = 1;
                if ( array_key_exists('desktop', $config) && array_key_exists('colCount', $config['desktop']) ) {
                    $colCount = intval($config['desktop']['colCount']);
                }
                if ($rowsToShow > 0 && $colCount > 0) {
                    $maxVisible = $rowsToShow * $colCount;
                    // Parse the markup and remove products beyond maxVisible
                    // Find the position of <ul> and </ul>
                    $ul_start = strpos($markup, '<ul');
                    $ul_end = strpos($markup, '</ul>', $ul_start);
                    if ($ul_start !== false && $ul_end !== false) {
                        $before_ul = substr($markup, 0, $ul_start);
                        $ul_open_tag_end = strpos($markup, '>', $ul_start) + 1;
                        $ul_content = substr($markup, $ul_open_tag_end, $ul_end - $ul_open_tag_end);
                        $after_ul = substr($markup, $ul_end);
                        
                        // Count and limit <li> elements using regex
                        $li_count = 0;
                        $limited_content = preg_replace_callback(
                            '/<li[^>]*>.*?<\/li>/s',
                            function($matches) use (&$li_count, $maxVisible) {
                                if ($li_count < $maxVisible) {
                                    $li_count++;
                                    return $matches[0];
                                }
                                return '';
                            },
                            $ul_content
                        );
                        
                        // Reconstruct markup with limited products
                        $markup = $before_ul . substr($markup, $ul_start, $ul_open_tag_end - $ul_start) . $limited_content . $after_ul;
                    }
                }
            }
        }

        // now add lay theme's extra configuration
        // find class="" attribute
        $pos = strpos($markup, 'class="');
        $ul_end_pos = strpos($markup, '</ul>');
        if( $pos !== FALSE ) {
            $first_part = substr($markup, 0, $pos);
            $second_part = substr($markup, $pos, 7);
            $third_part = substr($markup, $pos + 7, $ul_end_pos - $pos - 7);
            $fourth_part = substr($markup, $ul_end_pos);

            $showfilter = ( array_key_exists('showfilter', $config) && ($config['showfilter'] === 'true' || $config['showfilter'] === true) ) ? true : false;
            $showtagfilter = ( array_key_exists('showtagfilter', $config) && ($config['showtagfilter'] === 'true' || $config['showtagfilter'] === true) ) ? true : false;
            $tagFilterBehaviour = ( array_key_exists('tagFilterBehaviour', $config) ) ? $config['tagFilterBehaviour'] : 'or';
            $deselect_tags = ( array_key_exists('deselect_tags', $config) && ($config['deselect_tags'] === 'true' || $config['deselect_tags'] === true) ) ? true : false;
            $reorder_tags = ( array_key_exists('reorder_tags', $config) && ($config['reorder_tags'] === 'true' || $config['reorder_tags'] === true) ) ? true : false;
            // $limitThumbnails = ( array_key_exists('limitThumbnails', $config) ) ? $config['limitThumbnails'] : 0;
            $category = ( array_key_exists('category', $config) ) ? $config['category'] : '';
            $tag = ( array_key_exists('tag', $config) ) ? $config['tag'] : '';
            $showMoreButton = ( array_key_exists('showMoreButton', $config) && ($config['showMoreButton'] === true || $config['showMoreButton'] === 'true') ) ? true : false;

            $new_config = array(
                'desktop' => $config['desktop'],
                'tablet' => $config['tablet'],
                'phone' => $config['phone'],
                'layoutType' => $config['layoutType'],
                'showfilter' => $showfilter,
                'showtagfilter' => $showtagfilter,
                'tagFilterBehaviour' => $tagFilterBehaviour,
                'deselect_tags' => $deselect_tags,
                'reorder_tags' => $reorder_tags,
                'category' => $category,
                'tag' => $tag,
                // 'limitThumbnails' => $limitThumbnails
            );
            
            // Add Load More button config if enabled
            if ( array_key_exists('showMoreButton', $config) && ($config['showMoreButton'] === true || $config['showMoreButton'] === 'true') ) {
                $new_config['showMoreButton'] = true;
                $new_config['showMoreButtonStyle'] = ( array_key_exists('showMoreButtonStyle', $config) ) ? $config['showMoreButtonStyle'] : 'laybutton1';
                $new_config['moreButtonText'] = ( array_key_exists('moreButtonText', $config) && $config['moreButtonText'] !== '' ) ? $config['moreButtonText'] : 'Load More';
                $new_config['moreButtonAlignment'] = ( array_key_exists('moreButtonAlignment', $config) && in_array($config['moreButtonAlignment'], array('start', 'center', 'end')) ) ? $config['moreButtonAlignment'] : 'center';
                $new_config['moreButtonSpaceTop'] = ( array_key_exists('moreButtonSpaceTop', $config) && is_numeric($config['moreButtonSpaceTop']) ) ? intval($config['moreButtonSpaceTop']) : 20;
                if ( array_key_exists('desktop', $config) && array_key_exists('rowsToShow', $config['desktop']) ) {
                    $new_config['desktop']['rowsToShow'] = $config['desktop']['rowsToShow'];
                }
                if ( array_key_exists('tablet', $config) && array_key_exists('rowsToShow', $config['tablet']) ) {
                    $new_config['tablet']['rowsToShow'] = $config['tablet']['rowsToShow'];
                }
                if ( array_key_exists('phone', $config) && array_key_exists('rowsToShow', $config['phone']) ) {
                    $new_config['phone']['rowsToShow'] = $config['phone']['rowsToShow'];
                }
            }

            $new_classes = 'lay-products-thumbnails-grid '.$config['layoutType'];

            if( $showfilter ) {
                $new_classes .= ' use-filter';
            } else {
                $new_classes .= ' no-cats-filter';
            }
    
            if( $showtagfilter ) {
                $new_classes .= ' use-tags-filter';
            } else {
                $new_classes .= ' no-tags-filter';
            }

            $new_classes .= ' ';

            $masonry_sizer = '';
            if( $config['layoutType'] == 'masonry' ) {
                $masonry_sizer = '<div class="lay-productsgrid-gutter-sizer"></div><div class="lay-productsgrid-col-sizer"></div>';
            }

            $data_rows_shown = '';
            if ( $showMoreButton ) {
                $data_rows_shown = ' data-rows-shown="1"';
            }
            $new_markup = $first_part.' data-config="'.htmlspecialchars(json_encode($new_config)).'"'.$data_rows_shown.' '.$second_part.$new_classes.$third_part.$masonry_sizer.$fourth_part;
            // remove "columns-4", because it is woocommerce markup we dont need
            // not sure if this could also be a different value than 4 and not get removed but i think it's not that important
            $new_markup = str_replace('columns-4', '', $new_markup);
            $markup = $new_markup;
        }

        // Add Load More button if enabled (for both frontend and gridder)
        $showMoreButton = ( array_key_exists('showMoreButton', $config) && ($config['showMoreButton'] === true || $config['showMoreButton'] === 'true') ) ? true : false;
        if ($showMoreButton) {
            $showMoreButtonStyle = ( array_key_exists('showMoreButtonStyle', $config) ) ? $config['showMoreButtonStyle'] : 'laybutton1';
            $moreButtonText = ( array_key_exists('moreButtonText', $config) && $config['moreButtonText'] !== '' ) ? $config['moreButtonText'] : 'Load More';
            $moreButtonAlignment = ( array_key_exists('moreButtonAlignment', $config) && in_array($config['moreButtonAlignment'], array('start', 'center', 'end')) ) ? $config['moreButtonAlignment'] : 'center';
            $moreButtonSpaceTop = ( array_key_exists('moreButtonSpaceTop', $config) && is_numeric($config['moreButtonSpaceTop']) ) ? intval($config['moreButtonSpaceTop']) : 20;
            
            $loadmore_markup = '<div class="lay-productsgrid-load-more-wrap" style="justify-content:'.$moreButtonAlignment.';margin-top:'.$moreButtonSpaceTop.'px;">
                <div class="lay-productsgrid-load-more '.esc_attr($showMoreButtonStyle).'">'.esc_html($moreButtonText).'</div>
            </div>';
            $markup .= $loadmore_markup;
        }

        return $markup;
    }


    public static function lay_get_productsgrid_tagfilter_markup($config){
        // if( array_key_exists('showfilter', $config) && $config['showfilter'] == false ) {
            // only show tags that are in current category
        // }
        // $categories = get_categories( array( 'hide_empty' => true, 'orderby' => 'menu_order' ) );
        $sortedTagsArray;
        $useSortedTags = false;
        $filterMarkup = '';

        if( array_key_exists('sortedTagFilters', $config) && $config['sortedTagFilters'] && $config['sortedTagFilters'] != null) {
            $sortedTagsArray = $config['sortedTagFilters'];
            if( is_array($sortedTagsArray) && count($sortedTagsArray) > 0 ) {
                $useSortedTags = true;
            }
        }
        // $tags = get_tags();
        $tags = get_terms( array(
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
            // 'orderby' => 'menu_order'
        ) );

        if( $useSortedTags ) {

            // first see if we have any ids in $categories that are not in $sortedTagsArray
            // in that case user must have added new categories
            $sortedTagIds = array();
            $tagIds = array();
            foreach( $tags as $tag ) {
                if( is_object($tag) ) {
                    $tagIds []= $tag->term_id;
                }
            }
            foreach( $sortedTagsArray as $item ) {
                $sortedTagIds []= $item['id'];
            }

            foreach( $tagIds as $tagId ) {
                // a tag id is not in sortedTagIds! append it
                if( !in_array( $tagId, $sortedTagIds ) ){
                    $sortedTagsArray []= array('id' => $tagId);
                }
            }
            $ix = 0;
            // deselect tags button
            // pad with leading zeros for sorting
            if( array_key_exists('deselect_tags', $config) && $config['deselect_tags'] == true ) {
                $padded_ix = str_pad(strval($ix), 5, "0", STR_PAD_LEFT);
                $filterMarkup .= '<span data-ix="'.$padded_ix.'" class="ptag-bubble deselect-tags lay-filter-hidden" style="display:none;"></span>';
                $ix++;
            }
            foreach( $sortedTagsArray as $item_new ) {
                $id = $item_new['id'];
                foreach( $tags as $tag ) {
                    // this means non-existent categories that might still be saved in $sortedFilterArray won't be added
                    if( is_object($tag) && $id == $tag->term_id ) {
                        $visible_class = 'lay-filter-visible';
                        if( array_key_exists('visible', $item_new) ) {
                            if( $item_new['visible'] == 'true' || $item_new['visible'] == 1 ) {
                                $visible_class = 'lay-filter-visible';
                            }else{
                                $visible_class = 'lay-filter-hidden';
                            }
                        }
                        $visible_style = '';
                        if( $visible_class === 'lay-filter-hidden' ) {
                            $visible_style = 'style="display:none;"';
                        }
                        $padded_ix = str_pad(strval($ix), 5, "0", STR_PAD_LEFT);
                        $filterMarkup .= '<span data-ix="'.$padded_ix.'" data-tag_id="'.$tag->term_id.'" class="ptag-bubble '.$tag->slug.' '.$visible_class.'" '.$visible_style.'>'.self::get_term_name_for_qtranslate($tag, 'product_tag').'</span>';
                        $ix++;
                        continue;
                    }
                }
            }

        } else {

            $ix = 0;
            foreach ( $tags as $tag ) {   
                // pad with leading zeros for sorting
                $padded_ix = str_pad(strval($ix), 5, "0", STR_PAD_LEFT); 
                $filterMarkup .= '<span data-ix="'.$padded_ix.'" data-tag_id="'.$tag->term_id.'" class="ptag-bubble '.$tag->slug.'">'.self::get_term_name_for_qtranslate($tag, 'product_tag').'</span>';
                $ix++;
            }
        }
        $oneline = get_theme_mod('pgtf_oneline', 1) == 1 ? 'mobile-one-line' : 'mobile-not-one-line';
        return '<div class="_'.get_theme_mod('pgtf_textformat', 'Default').'_no_spaces lay-productsgrid-tagfilter '.$oneline.'">'.$filterMarkup.'</div>';
    }

    public static function lay_get_productsgrid_category_filter_markup($config = array()){
        // when this partial reloads because of the customizer, it won't have a $config passed
        // in this case, just use default catgory as active category for filter
        // this will not represent the shown thumbnails underneath but at least there'll be an active menupoint for styling purposes
        $default_catid = get_option( 'default_product_cat' );

        $default_filter_catid_chosen_for_this_filter = array_key_exists('default_filter_category', $config) ? $config['default_filter_category'] : $default_catid;

        $showfilteramounts = array_key_exists('showfilteramounts', $config) ? $config['showfilteramounts'] : false;

        $categories = get_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'menu_order' ) );
        $sortedFilterArray;
        $useSortedFilter = false;

        if( array_key_exists('sortedFilters', $config) && $config['sortedFilters'] && $config['sortedFilters'] != null) {
            $sortedFilterArray = $config['sortedFilters'];
            if( is_array($sortedFilterArray) && count($sortedFilterArray) > 0 ) {
                $useSortedFilter = true;
            }
        }

        $oneline = get_theme_mod('pgf_oneline', 1) == 1 ? 'mobile-one-line' : 'mobile-not-one-line';
        $filterMarkup = '<div class="lay-productsgrid-filter _'.get_theme_mod('pgf_textformat', 'Default').'_no_spaces '.$oneline.'">';
        if( $useSortedFilter ) {
            // first see if we have any ids in $categories that are not in $sortedFilterArray
            // in that case user must have added new categories
            $sortedFilterIds = array();
            $categoryIds = array();
            foreach( $categories as $category ) {
                $categoryIds []= $category->term_id;
            }
            foreach( $sortedFilterArray as $item ) {
                $sortedFilterIds []= $item['term_id'];
            }

            foreach( $categoryIds as $catId ) {
                // a category id is not in sortedFilterIds! append it
                if( !in_array( $catId, $sortedFilterIds ) ){
                    $sortedFilterArray []= array('term_id' => $catId);
                }
            }

            foreach( $sortedFilterArray as $item_new ) {
                $id = $item_new['term_id'];
                foreach( $categories as $category ) {

                    $amount_of_products = '';
                    if($showfilteramounts == 'true' || $showfilteramounts == 1) {
                        $amount_of_products = ' <span class="filter-amount">'.$category->count.'</span>';
                    }

                    // this means non-existent categories that might still be saved in $sortedFilterArray won't be added
                    if( $id == $category->term_id ) {
                        $category_name = self::get_term_name_for_qtranslate($category, 'product_cat');
                        $category_slug = $category->slug;
                        $visible_class = 'lay-filter-visible';
                        if( array_key_exists('visible', $item_new) ) {

                            if( $item_new['visible'] == 1 || $item_new['visible'] == true ) {
                                $visible_class = 'lay-filter-visible';
                            }else{
                                $visible_class = 'lay-filter-hidden';
                            }
                        }
                        $visible_style = '';
                        if( $visible_class === 'lay-filter-hidden' ) {
                            $visible_style = 'style="display:none;"';
                        }
                        // active filter
                        // if( array_key_exists('cat_id', $config) && $config['cat_id'] == $id ) {
                        if( $id == $default_filter_catid_chosen_for_this_filter ){
                            $filterMarkup .= '<div class="lay-productsgrid-filter-anchor '.($showfilteramounts ? 'show-filter-amount' : '').' lay-filter-active lay-filter-default '.$visible_class.'" '.$visible_style.' data-slug="'.$category_slug.'" data-id="'.$id.'">'.$category_name.$amount_of_products.'</div>';
                        } else {
                            $filterMarkup .= '<div class="lay-productsgrid-filter-anchor '.($showfilteramounts ? 'show-filter-amount' : '').' '.$visible_class.'" data-slug="'.$category_slug.'" '.$visible_style.' data-id="'.$id.'">'.$category_name.$amount_of_products.'</div>';
                        }
                    }
                }
            }
            $filterMarkup .= '</div>';
        } else {
            $first_anchor = '';
            $other_anchors = '';
    
            foreach( $categories as $category ) {
                $id = $category->term_id;
                $category_name = self::get_term_name_for_qtranslate($category, 'product_cat');
                $category_slug = $category->slug;
                // first anchor is always the default category
                if( $default_filter_catid_chosen_for_this_filter == $id ) {
                    $first_anchor = '<div class="lay-productsgrid-filter-anchor '.($showfilteramounts ? 'show-filter-amount' : '').' lay-filter-active lay-filter-default" data-slug="'.$category_slug.'" data-id="'.$id.'">'.$category_name.$amount_of_products.'</div>';
                } else {
                    $other_anchors .= '<div class="lay-productsgrid-filter-anchor '.($showfilteramounts ? 'show-filter-amount' : '').'" data-slug="'.$category_slug.'" data-id="'.$id.'">'.$category_name.$amount_of_products.'</div>';
                }
            }
            $filterMarkup .= $first_anchor.$other_anchors.'</div>';
        }

        return $filterMarkup;
    }

    // using this in a customizer partial, so for convenience i put this function here
    public static function lay_get_productsgrid_filter_markup($config = array()){
        $filterMarkup = '';
        $use_tag_filter = array_key_exists('showtagfilter', $config) ? $config['showtagfilter'] : false;
        $use_cat_filter = array_key_exists('showfilter', $config) ? $config['showfilter'] : false;
        // when gridder ajax: 'true'. when just frontend: 1 lmao
        if( $use_cat_filter == 'true' || $use_cat_filter == 1 ) {
            $filterMarkup .= LayProductsgridFunctions::lay_get_productsgrid_category_filter_markup($config);
        }
        if( $use_tag_filter == 'true' || $use_tag_filter == 1 ) {
            $filterMarkup .= LayProductsgridFunctions::lay_get_productsgrid_tagfilter_markup($config);
        }
        return $filterMarkup;
    }

}
new LayProductsgridFunctions();
