<?php
// todo: make project prevnext work with this project order

class ProductsgridForGridder{

	public function __construct(){
		add_action('wp_ajax_get_category_filter_list_for_productsgrid', array($this, 'get_category_filter_list_for_productsgrid'));
		add_action('wp_ajax_get_tag_list_for_productsgrid', array($this, 'get_tag_list_for_productsgrid'));
    }

    public static function get_tag_list_for_productsgrid(){
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
                $sortedTagIds []= $item['id'];
            }
			// check if there are new cat ids
            foreach( $tagIds as $tagId ) {
                // a tag id is not in sortedTagIds! append it
                if( !in_array( $tagId, $sortedTagIds ) ){
                    $sortedTagsArray []= array('id' => $tagId, 'visible' => true);
                }
			}

			// also update names in case tag names were changed
			foreach( $tags as $tag ) {
				foreach( $sortedTagsArray as &$item ) {
					if( $item['id'] == $tag->term_id ) {
						$item['name'] = $tag->name;
						$item['slug'] = $tag->slug;
						break;
					}
				}
			}

			// and only put in tags that still exist
			$sortedTagsArrayReturn = array();
			foreach( $sortedTagsArray as $item2 ){
				foreach( $tags as $tag ) {
					if( $item2['id'] == $tag->term_id ) {
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
			$tags2 = array();
			foreach( $tags as $tag ) {
				$tags2 []= array('id' => $tag->term_id, 'visible' => true, 'name' => $tag->name, 'slug' => $tag->slug );
			}
			$json = json_encode( $tags2 );
			wp_send_json($json);
		}
		die();
	}

	public static function get_category_filter_list_for_productsgrid(){
		$sortedFilterArray = array_key_exists('filter', $_POST) ? $_POST['filter'] : null;
		$categories = get_categories( array('taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'menu_order') );

		if( is_array($sortedFilterArray) ){
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
			// check if there are new cat ids
            foreach( $categoryIds as $catId ) {
                // a category id is not in sortedFilterIds! append it
                if( !in_array( $catId, $sortedFilterIds ) ){
                    $sortedFilterArray []= array('term_id' => $catId);
                }
			}

			// also update names in case category names were changed
			foreach( $categories as $category ) {
				foreach( $sortedFilterArray as &$item ) {
					if( $item['term_id'] == $category->term_id ) {
						$item['name'] = $category->name;
						break;
					}
				}
			}

			// and only put in categories that still exist
			$sortedFilterArrayReturn = array();
			foreach( $sortedFilterArray as $item2 ){
				foreach( $categories as $category ) {
					if( $item2['term_id'] == $category->term_id ) {
						$sortedFilterArrayReturn []= $item2;
					}
				}
			}
			// now convert to objects 
			foreach( $sortedFilterArrayReturn as &$item3 ) {
				$item3 = (object)$item3;
			}
			$json = json_encode( $sortedFilterArrayReturn );
			wp_send_json($json);
		}else{
			$json = json_encode( $categories );
			wp_send_json($json);
		}
		die();
	}


}
new ProductsgridForGridder();
