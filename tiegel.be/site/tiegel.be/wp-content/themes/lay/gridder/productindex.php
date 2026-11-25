<?php

class ProductIndex{

    public function __construct(){
		if ( is_admin() ) {
			add_action('wp_ajax_get_productindex', array($this, 'get_productindex_via_ajax'));
            add_action('wp_ajax_get_category_list_for_productindex', array($this, 'get_category_list_for_productindex'));
            add_action('wp_ajax_get_tag_list_for_productindex', array($this, 'get_tag_list_for_productindex'));
		}
    }

    public static function get_tag_list_for_productindex(){
		$sortedTagsArray = $_POST['tags'];
		$tags = get_terms( array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
            'orderby' => 'menu_order'
        ) );

		if( is_array($sortedTagsArray) ){
            // first see if we have any ids in $tags that are not in $sortedTagsArray
            // in that case user must have added new tags
            $sortedTagIds = array();
            $tagIds = array();
            foreach( $tags as $tag ) {
                $tagIds []= $tag->term_id;
            }
            foreach( $sortedTagsArray as $item ) {
                $sortedTagIds []= $item['term_id'];
            }
			// check if there are new cat ids
            foreach( $tagIds as $tagId ) {
                // a tag id is not in sortedTagIds! append it
                if( !in_array( $tagId, $sortedTagIds ) ){
                    $sortedTagsArray []= array('term_id' => $tagId, 'visible' => true);
                }
			}

			// also update names in case tag names were changed
			foreach( $tags as $tag ) {
				foreach( $sortedTagsArray as &$item ) {
					if( $item['term_id'] == $tag->term_id ) {
						$item['name'] = $tag->name;
						break;
					}
				}
			}

			// and only put in tags that still exist
			$sortedTagsArrayReturn = array();
			foreach( $sortedTagsArray as $item2 ){
				foreach( $tags as $tag ) {
					if( $item2['term_id'] == $tag->term_id ) {
						$sortedTagsArrayReturn []= $item2;
					}
				}
			}
			// now convert to objects 
			foreach( $sortedTagsArrayReturn as &$item3 ) {
				$item3 = (object)$item3;
			}
			$json = json_encode( $sortedTagsArrayReturn );
			wp_send_json($json);
		}else{
			$json = json_encode( $tags );
			wp_send_json($json);
		}
		die();
	}

    public static function usortCat($a, $b) {
        if ($a['cats_markup'] == $b['cats_markup']) {
            return 0;
        }
        $cmp = strcmp($a['cats_markup'], $b['cats_markup']);
        $value = 0;
        if( $cmp > 0 ) {
            $value = 1;
        } else if ( $cmp < 0 ) {
            $value = -1;
        }
        return $value;
    }

    public static function usortTag($a, $b) {
        return strnatcasecmp($a['tags_markup'], $b['tags_markup']);
    }

    public static function usortTitle($a, $b) {
        if ($a['post_title'] == $b['post_title']) {
            return 0;
        }
        $cmp = strcmp($a['post_title'], $b['post_title']);
        $value = 0;
        if( $cmp > 0 ) {
            $value = 1;
        } else if ( $cmp < 0 ) {
            $value = -1;
        }
        return $value; 
    }

    public static function usortDate($a, $b) {
        $a_date = get_the_date('Ymd', $a['post_id']);
        $b_date = get_the_date('Ymd', $b['post_id']);
        if ($a_date == $b_date) {
            return 0;
        }

        return strtotime($a_date) - strtotime($b_date);
    }

    public static function orderRows(&$rows, $orderby, $order){
        switch( $orderby ) {
            case 'categories':
                usort($rows, 'ProductIndex::usortCat');
            break;
            case 'tags':
                usort($rows, 'ProductIndex::usortTag');
            break;
            case 'title':
                usort($rows, 'ProductIndex::usortTitle');
            break;
            case 'date':
                usort($rows, 'ProductIndex::usortDate');
            break;
        }

        if($order == 'DESC'){
            $rows = array_reverse($rows);
        }
    }

    public static function get_category_list_for_productindex(){
		$sortedCategoriesArray = array_key_exists('categories', $_POST) ? $_POST['categories'] : null;
		$categories = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'menu_order'
        ) );

		if( is_array($sortedCategoriesArray) ){
            // first see if we have any ids in $categories that are not in $sortedCategoriesArray
            // in that case user must have added new categories
            $sortedCatIds = array();
            $categoryIds = array();
            foreach( $categories as $category ) {
                $categoryIds []= $category->term_id;
            }
            foreach( $sortedCategoriesArray as $item ) {
                $sortedCatIds []= $item['term_id'];
            }
			// check if there are new cat ids
            foreach( $categoryIds as $catId ) {
                // a category id is not in sortedCatIds! append it
                if( !in_array( $catId, $sortedCatIds ) ){
                    $sortedCategoriesArray []= array('term_id' => $catId, 'visible' => true);
                }
			}

			// also update names in case category names were changed
			foreach( $categories as $category ) {
				foreach( $sortedCategoriesArray as &$item ) {
					if( $item['term_id'] == $category->term_id ) {
						$item['name'] = $category->name;
						break;
					}
				}
			}

			// and only put in categories that still exist
			$sortedCategoriesArrayReturn = array();
			foreach( $sortedCategoriesArray as $item2 ){
				foreach( $categories as $category ) {
					if( $item2['term_id'] == $category->term_id ) {
						$sortedCategoriesArrayReturn []= $item2;
					}
				}
			}
			// now convert to objects 
			foreach( $sortedCategoriesArrayReturn as &$item3 ) {
				$item3 = (object)$item3;
			}
			$json = json_encode( $sortedCategoriesArrayReturn );
			wp_send_json($json);
		}else{
			$json = json_encode( $categories );
			wp_send_json($json);
		}
		die();
	}

    public static function get_productindex_via_ajax() {
        $config = $_POST['config'];
        $uniqueId = array_key_exists('uniqueId', $_POST) ? $_POST['uniqueId'] : '';
        $currentLayout = array_key_exists('currentLayout', $_POST) ? $_POST['currentLayout'] : 'desktop';
        echo ProductIndex::get_productindex($config, $currentLayout);
        die();
    }

    public static function get_productindex($config, $currentLayout) {
        if( !class_exists('WooCommerce') ) {
            return '<div class="lay-productindex-error _Default_no_spaces">WooCommerce Productindex. Enable WooCommerce to show.</div>';
        }
        $orderby = '';
        if( is_array($config) ) {
            $orderby_temp = $config['orderBy'];
            $order = $config['order'];
        }
        // when order by is category or tag, i need to order manually, cause wordpress doesnt accept these arguments for order
        if( $orderby_temp != 'category' && $orderby_temp != 'tag' ) {
            $orderby = $orderby_temp;
        }

        $query_args = array(
            'post_status' => 'publish',
            'orderby' => $orderby,
            'order' => $order,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'has_password' => false,
            'post_type' => 'product'
        );

        $category_term_slugs = array();
        if( is_array( $config['category'] ) ) {
            foreach ($config['category'] as $cat) {
                $category_term_slugs []= $cat['value'];
            }
        }
        if( is_array($category_term_slugs) && count($category_term_slugs) > 0 ) {
            $query_args['product_cat'] = implode(',', $category_term_slugs);
        }
        $style = array_key_exists('style', $config) ? $config['style'] : 'table';
        $separator = ( $style == 'comma' || $style == 'comma2' ) ? '<span class="lay-pi-separator">,&nbsp;</span>' : '';
        $separator2 = ( $style == 'comma' || $style == 'comma2' ) ? '<span class="_'.$config['filtersTextformat'].'_no_spaces lay-pi-separator">,&nbsp;</span>' : '';

        $uniqueId = array_key_exists('uniqueId', $config) ? $config['uniqueId'] : '';
        $filterColor = array_key_exists('filterColor', $config) ? $config['filterColor'] : $config['textColor'];
        $filterHoverColor = array_key_exists('filterHoverColor', $config) ? $config['filterHoverColor'] : $filterColor;

        $filterHoverCSS = '';
        if( array_key_exists('doFilterHoverColor', $config) && $config['doFilterHoverColor'] == true ) {
            $filterHoverCSS = '
                .lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-inner:hover polyline{ stroke:'.$filterHoverColor.'; }
                .lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-inner:hover{ color:'.$filterHoverColor.'; }
                .lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-inner:hover span{ color:'.$filterHoverColor.'; }
            ';
        }

        $paddingDesktop = array_key_exists('paddingLeftRight', $config['desktop']) ? $config['desktop']['paddingLeftRight'] : 0;
        $paddingTablet = array_key_exists('paddingLeftRight', $config['tablet']) ? $config['tablet']['paddingLeftRight'] : 0;
        $paddingPhone = array_key_exists('paddingLeftRight', $config['phone']) ? $config['phone']['paddingLeftRight'] : 0;

        $phone_breakpoint = get_option('lay_breakpoint', 600);
        $phone_breakpoint = (int)$phone_breakpoint;

		$tablet_breakpoint = get_option( 'lay_tablet_breakpoint', 1024 );
        $tablet_breakpoint = (int)$tablet_breakpoint;

        $date_format = array_key_exists('date_format', $config) ? $config['date_format'] : 'Y';
        
        $doActiveColor = array_key_exists('doActiveColor', $config) ? $config['doActiveColor'] : 'true';
        $doActiveColor = $doActiveColor == 'true' ? true : false;
        
        $activeCSS = '.lay-productindex.id-'.$uniqueId.' .lay-product-index-current-product{ background-color:'.$config['activeColor'].'; }';
        if($doActiveColor == false) {
            $activeCSS = '';
        }

        $productindex_css = 
        '<style>
            @media (min-width: '.($tablet_breakpoint+1).'px){'
                .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-row,
                .lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-wrap{
                    padding-left:'.$paddingDesktop.'px;
                    padding-right:'.$paddingDesktop.'px;
                }'
            .'}
            @media (max-width: '.($tablet_breakpoint).'px) and (min-width: '.($phone_breakpoint+1).'px){'
                .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-row,
                .lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-wrap{
                    padding-left:'.$paddingTablet.'px;
                    padding-right:'.$paddingTablet.'px;
                }'
            .'}
            @media (max-width: '.($phone_breakpoint).'px){'
                .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-row,
                .lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-wrap{
                    padding-left:'.$paddingPhone.'px;
                    padding-right:'.$paddingPhone.'px;
                }'
            .'}
            '.$activeCSS
            .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-inner polyline{ stroke:'.$filterColor.'; }'
            .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-inner{ color:'.$filterColor.'; }'
            .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-filter-inner span{ color:'.$filterColor.'!important; }'
            .$filterHoverCSS
            .'.lay-productindex.id-'.$uniqueId.' .lay-productindex-col{ color:'.$config['textColor'].'; }'
            .(array_key_exists('doHoverColor', $config) && $config['doHoverColor'] == true ? '.lay-productindex.lay-productindex-imagehover-not-active.id-'.$uniqueId.' .lay-productindex-row:hover>div, .lay-productindex.lay-productindex-imagehover-active.id-'.$uniqueId.' .lay-productindex-row.hover>div{ color:'.$config['hoverColor'].'; }' : '')    
            .(array_key_exists('doBgHoverColor', $config) && $config['doBgHoverColor'] == true ? '.lay-productindex.lay-productindex-imagehover-not-active.id-'.$uniqueId.' .lay-productindex-row:hover, .lay-productindex.lay-productindex-imagehover-active.id-'.$uniqueId.' .lay-productindex-row.hover{ background-color:'.$config['bgHoverColor'].'; }' : '')    
        .'</style>';

        $imagehover_active = $config['useImageHover'] && is_plugin_active('laytheme-imagehover/laytheme-imagehover.php') == true ? 'lay-productindex-imagehover-active' : 'lay-productindex-imagehover-not-active';
        $imagehover_videos_active = array_key_exists('useImageHoverVideos', $config) && $config['useImageHoverVideos'] && is_plugin_active('laytheme-imagehover/laytheme-imagehover.php') == true ? 'lay-productindex-imagehover-videos-active' : 'lay-productindex-imagehover-videos-not-active';

        $lay_productindex = '<div class="lay-productindex lay-productindex-style-'.$style.' id-'.$uniqueId.' '.$imagehover_active.'" data-config="'.htmlspecialchars(json_encode($config)).'">';

        // filter!
        $showFilter = ($config['showFilters'] === 'true' || $config['showFilters'] === true) ? true : false;

        if( $showFilter === true ) {
            $lay_productindex .= '<div class="lay-productindex-filter-wrap">';

            foreach ($config[$currentLayout]['sortedColumns'] as $column) {
                $css = 'padding-top:'.$config['spaceTopBottom'].'px; padding-bottom:'.$config['spaceTopBottom'].'px;';
                if( $column['visible'] === 'false' || $column['visible'] === false ) {
                    $css .= 'display:none;';
                }

                switch( $column['name'] ) {
                    case 'year':
                        $active = $orderby == 'date' ? 'productindex-filter-active' : '';
                        $lay_productindex .= 
                        '<div class="'.$active.' order-'.$order.' lay-productindex-filter lay-productindex-filter-year" style="'.$css.'">
                            <div class="lay-productindex-filter-inner">
                                <span class="_'.$config['filtersTextformat'].'_no_spaces">'.$config['yearFilterText'].'</span>
                                <svg height="9" width="16"><polyline fill="none" stroke="black" points="1,0 8,8 15,0"></polyline></svg>
                                '.$separator2.'
                            </div>
                        </div>';
                    break;
                    case 'title':
                        $active = $orderby == 'title' ? 'productindex-filter-active' : '';
                        $lay_productindex .= 
                        '<div class="'.$active.' order-'.$order.' lay-productindex-filter lay-productindex-filter-title" style="'.$css.'">
                            <div class="lay-productindex-filter-inner">
                                <span class="_'.$config['filtersTextformat'].'_no_spaces">'.$config['titleFilterText'].'</span>
                                <svg height="9" width="16"><polyline fill="none" stroke="black" points="1,0 8,8 15,0"></polyline></svg>
                                '.$separator2.'
                            </div>
                        </div>';
                    break;
                    case 'categories':
                        $active = $orderby == 'categories' ? 'productindex-filter-active' : '';
                        $lay_productindex .= 
                        '<div class="'.$active.' order-'.$order.' lay-productindex-filter lay-productindex-filter-categories" style="'.$css.'">
                            <div class="lay-productindex-filter-inner">
                                <span class="_'.$config['filtersTextformat'].'_no_spaces">'.$config['categoriesFilterText'].'</span>
                                <svg height="9" width="16"><polyline fill="none" stroke="black" points="1,0 8,8 15,0"></polyline></svg>
                                '.$separator2.'
                            </div>
                        </div>';
                    break;
                    case 'tags':
                        $active = $orderby == 'tags' ? 'productindex-filter-active' : '';
                        $lay_productindex .= 
                        '<div class="'.$active.' order-'.$order.' lay-productindex-filter lay-productindex-filter-tags" style="'.$css.'">
                            <div class="lay-productindex-filter-inner">
                                <span class="_'.$config['filtersTextformat'].'_no_spaces">'.$config['tagsFilterText'].'</span>
                                <svg height="9" width="16"><polyline fill="none" stroke="black" points="1,0 8,8 15,0"></polyline></svg>
                                '.$separator2.'
                            </div>
                        </div>';
                    break;
                }
            }

            $lay_productindex .= '</div>';
        }

        $rows = array();
        
        $query = new WP_Query($query_args);

        $current_post_id = false;
        global $post;
        if( is_object($post) ) {
            $current_post_id = $post->ID;
        }

        if ( $query->have_posts() ) {
            foreach ($query->posts as $id) {
                $cat_names = wp_get_post_terms($id, 'product_cat', array('fields' => 'names', 'orderby' => 'term_order'));
                $cat_ids = wp_get_post_terms($id, 'product_cat', array('fields' => 'ids', 'orderby' => 'term_order'));

                // order categories by user order:
                if ( array_key_exists('sortedCategories', $config) && is_array($config['sortedCategories']) ) {
                    $ordered_result_cats = array();
                    $hidden_cats = array();

                    foreach ($config['sortedCategories'] as $ordered_cat) {
                        $v = $ordered_cat['visible'];

                        if( gettype($v) == 'string' ) {
                            if($ordered_cat['visible'] === 'false' || $ordered_cat['visible'] === false){
                                $v = false;
                            }
                            else if($ordered_cat['visible'] === 'true' || $ordered_cat['visible'] === true){
                                $v = true;
                            }
                        }

                        if( $v == true ) {
                            $term = get_term_by('term_id', $ordered_cat['term_id'], 'product_cat');
                            if( $term !== false ) {
                                if(array_search($term->name, $cat_names) !== false){
                                    $ordered_result_cats []= $term->name;
                                }
                            }
                        } else {
                            $term = get_term_by('term_id', $ordered_cat['term_id'], 'product_cat');
                            if( $term !== false ) {
                                if(array_search($term->name, $cat_names) !== false){
                                    $hidden_cats []= $term->name;
                                }
                            }
                        }
                    }

                    $cats_to_add = [];
                    if(is_array($cat_names) && count($cat_names) > count($ordered_result_cats)){
                        foreach($cat_names as $name){
                            if(array_search($name, $ordered_result_cats) === false && array_search($name, $hidden_cats) === false){
                                $cats_to_add []= $name;
                            }
                        }
                    }

                    $cat_names = array_merge($ordered_result_cats, $cats_to_add);
                }

                $categoriesSeparator = array_key_exists('categoriesSeparator', $config) ? $config['categoriesSeparator'] : ', ';
                $cats_markup = implode($categoriesSeparator, $cat_names);

                $tags = wp_get_post_terms($id, 'product_tag');

                $tag_names = array();
                if ($tags) {
                    usort($tags, function($a, $b) {
                        return $a->term_order - $b->term_order;
                    });

                    foreach($tags as $tag) {
                        $tag_names []= $tag->name;
                    }
                }

                if ( array_key_exists('sortedTags', $config) && is_array($config['sortedTags']) && $tags ) {
                    $ordered_result_tags = array();
                    foreach ($config['sortedTags'] as $ordered_tag) {
                        $v = $ordered_tag['visible'];
                        if( gettype($v) == 'string' ) {
                            if($ordered_tag['visible'] == 'false'){
                                $v = false;
                            }
                            else if($ordered_tag['visible'] == 'true'){
                                $v = true;
                            }
                        }
                        if( $v == true ) {
                            $term = get_term_by('term_id', $ordered_tag['term_id'], 'product_tag');
                            if( $term !== false) {
                                if(array_search($term->name, $tag_names) !== false) {
                                    $ordered_result_tags []= $term->name;
                                }
                            }
                        }
                    }

                    $tags_to_add = [];
                    if(is_array($tag_names) && count($tag_names) > count($ordered_result_tags)){
                        foreach($tag_names as $name){
                            if(array_search($name, $ordered_result_tags) === false){
                                $tags_to_add []= $name;
                            }
                        }
                    }

                    $tag_names = array_merge($ordered_result_tags, $tags_to_add);
                }

                $tagsSeparator = array_key_exists('tagsSeparator', $config) ? $config['tagsSeparator'] : ', ';
                $tags_markup = implode($tagsSeparator, $tag_names);

                $rows []= array(
                    'post_id' => $id,
                    'cat_ids' => $cat_ids,
                    'post_link' => get_permalink($id),
                    'post_name' => get_post_field( 'post_name', $id ),
                    'post_title' => get_the_title( $id ),
                    'post_date' => get_the_date($date_format, $id),
                    'cats_markup' => $cats_markup,
                    'tags_markup' => $tags_markup
                );
            }

            ProductIndex::orderRows($rows, $orderby, $order);
            
            $ix = 0;
            foreach ($rows as $row) {
                $border_style = 'border-bottom:'.$config['lineStrokeWidth'].'px solid '.$config['lineColor'].';';
                $extraclass = '';
                if( $ix == 0 ) {
                    $extraclass = 'lay-productindex-first-row';
                    $border_style .= 'border-top:'.$config['lineStrokeWidth'].'px solid '.$config['lineColor'].';';
                }

                $current_post_class = $current_post_id == $row['post_id'] ? 'lay-product-index-current-product' : '';
                if( LayFrontend_Options::$hide_current_project_from_thumbnailgrid == 'on' && $current_post_id == $row['post_id'] ){
                    continue;
                }
                $lay_productindex .= 
                '<div class="lay-productindex-row '.$current_post_class.' productindex-product-slug-'.$row['post_name'].' _'.$config['textformat'].'_no_spaces '.$extraclass.'" style="'.$border_style.' padding-top:'.$config['spaceTopBottom'].'px; padding-bottom:'.$config['spaceTopBottom'].'px;">';
                
                foreach ($config[$currentLayout]['sortedColumns'] as $column) {
                    $css = '';
                    if( $column['visible'] === 'false' || $column['visible'] === false ) {
                        $css = 'style="display:none;"';
                    }

                    switch( $column['name'] ) {
                        case 'year':
                            $lay_productindex .= 
                            '<div class="lay-productindex-col-year lay-productindex-col" data-date="'.get_the_date('Ymd', $row['post_id']).'" '.$css.'>'.$row['post_date'].$separator.'</div>';
                        break;
                        case 'title':
                            $lay_productindex .= 
                            '<div class="lay-productindex-col-title lay-productindex-col" '.$css.'>'.$row['post_title'].$separator.'</div>';
                        break;
                        case 'categories':
                            $lay_productindex .= 
                            '<div class="lay-productindex-col-categories lay-productindex-col" '.$css.'>'.$row['cats_markup'].$separator.'</div>';
                        break;
                        case 'tags':
                            $emptyClass = $row['tags_markup'] == '' ? 'lay-productindex-col-empty' : ''; 
                            $lay_productindex .= 
                            '<div class="lay-productindex-col-tags lay-productindex-col '.$emptyClass.'" '.$css.'>'.$row['tags_markup'].$separator.'</div>';
                        break;
                    }
                }

                $imagehover_attr = '';
                if( $config['useImageHover'] == true && is_plugin_active('laytheme-imagehover/laytheme-imagehover.php') ) {
                    $video_id = "";
                    if ( VideoThumbnails::$active == true ) {
                        $video_id = get_post_meta( $row['post_id'], '_lay_thumbnail_video', true );
                    }
                    if ( array_key_exists('useImageHoverVideos', $config) && $config['useImageHoverVideos'] == true && $video_id != -1 && $video_id != "" ) {
                        $imagehover_attr = 'data-hoverimageid="'.$video_id.'" data-hoverimagetype="video"';
                    } else {
                        $imagehover_attr = 'data-hoverimageid="'.get_post_thumbnail_id($row['post_id']).'"';
                    }
                }

                $link_tag = 'a';
                $href = 'href="'.$row['post_link'].'"';
                $disable_link_is_active = get_post_meta($row['post_id'], 'disable_link_is_active', false);
                if(is_array($disable_link_is_active) && array_key_exists(0, $disable_link_is_active) && $disable_link_is_active[0] == 'on'){
                    $link_tag = 'span';
                    $href = '';
                }

                $lay_productindex .= '<'.$link_tag.' class="productindex-product-link" '.$href.'  '.$imagehover_attr.' data-type="product" data-catid="'.LayElFunctions::stringifyCatIds($row['cat_ids']).'" data-id="'.$row['post_id'].'" data-title="'.__($row['post_title']).'"></'.$link_tag.'>';
                $lay_productindex .= '</div>';
                $ix++;
            }
        }
        $lay_productindex .= '</div>';

        return $lay_productindex.$productindex_css;
    }
}
new ProductIndex(); 