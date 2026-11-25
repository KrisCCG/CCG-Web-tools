<?php

class ProjectIndex {
	public function __construct() {
		if (is_admin()) {
			add_action('wp_ajax_get_projectindex', [$this, 'get_projectindex_via_ajax']);
			add_action('wp_ajax_get_category_list_for_projectindex', [$this, 'get_category_list_for_projectindex']);
			add_action('wp_ajax_get_tag_list_for_projectindex', [$this, 'get_tag_list_for_projectindex']);
		}
	}

	/** Lay Options → Other: "Custom Project Index Date Field" */
	public static function projectindex_custom_year_enabled() {
		return get_option('misc_options_custom_project_index_date_field', '') === 'on';
	}

	/**
	 * @return string|null Custom year display like "1999" or "1999 - 2004"
	 *                      or null to use publish date.
	 */
	private static function get_stored_projectindex_year($post_id) {
		if (!self::projectindex_custom_year_enabled()) {
			return null;
		}
		$raw = get_post_meta($post_id, '_lay_projectindex_year', true);
		if ($raw === '' || $raw === false) {
			return null;
		}

		$normalized = preg_replace('/[–—]/', '-', (string) $raw);
		$normalized = preg_replace('/\s+/', ' ', trim($normalized));

		// Single year: "1999"
		if (preg_match('/^(\d{4})$/', $normalized, $m)) {
			$y = (int) $m[1];
			if ($y < 1000 || $y > 9999) {
				return null;
			}
			return (string) $y;
		}

		// Range year: "1999 - 2004"
		if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $normalized, $m)) {
			$start = (int) $m[1];
			$end = (int) $m[2];
			if ($start < 1000 || $start > 9999 || $end < 1000 || $end > 9999) {
				return null;
			}
			// Keep chronological order for sorting key and display.
			if ($start > $end) {
				$tmp = $start;
				$start = $end;
				$end = $tmp;
			}

			return $start . ' - ' . $end;
		}

		// Not a recognized format.
			return null;
	}

	public static function get_projectindex_row_date_ymd($post_id) {
		$stored = self::get_stored_projectindex_year($post_id);
		if ($stored !== null) {
			// Sorting key: use the *start* year (e.g. "1999 - 2004" -> 1999).
			if (!preg_match('/^(\d{4})/', $stored, $m)) {
				return get_the_date('Ymd', $post_id);
			}
			$y_start = (int) $m[1];

			$post_date = get_post_field('post_date', $post_id);
			$ts = $post_date ? strtotime($post_date) : false;
			$md = $ts ? date('md', $ts) : '0101';
			return sprintf('%04d%s', $y_start, $md);
		}
		return get_the_date('Ymd', $post_id);
	}

	public static function get_projectindex_row_year_display($post_id, $date_format) {
		$stored = self::get_stored_projectindex_year($post_id);
		if ($stored !== null) {
			// For "Year for Projectindex Element" we always display the custom string.
			// (Sorting uses data-date generated from the start year.)
			return (string) $stored;
		}
		return get_the_date($date_format, $post_id);
	}

	public static function get_tag_list_for_projectindex() {
		$sortedTagsArray = array_key_exists('tags', $_POST) ? $_POST['tags'] : null;
		$tags = get_terms([
			'taxonomy' => 'post_tag',
			'hide_empty' => false,
			'orderby' => 'menu_order',
		]);

		if (is_array($sortedTagsArray)) {
			// first see if we have any ids in $tags that are not in $sortedTagsArray
			// in that case user must have added new tags
			$sortedTagIds = [];
			$tagIds = [];
			foreach ($tags as $tag) {
				$tagIds[] = $tag->term_id;
			}
			foreach ($sortedTagsArray as $item) {
				$sortedTagIds[] = $item['term_id'];
			}
			// check if there are new cat ids
			foreach ($tagIds as $tagId) {
				// a tag id is not in sortedTagIds! append it
				if (!in_array($tagId, $sortedTagIds)) {
					$sortedTagsArray[] = ['term_id' => $tagId, 'visible' => true];
				}
			}

			// also update names in case tag names were changed
			foreach ($tags as $tag) {
				foreach ($sortedTagsArray as &$item) {
					if ($item['term_id'] == $tag->term_id) {
						$item['name'] = $tag->name;
						break;
					}
				}
			}

			// and only put in tags that still exist
			$sortedTagsArrayReturn = [];
			foreach ($sortedTagsArray as $item2) {
				foreach ($tags as $tag) {
					if ($item2['term_id'] == $tag->term_id) {
						$sortedTagsArrayReturn[] = $item2;
					}
				}
			}
			// now convert to objects
			foreach ($sortedTagsArrayReturn as &$item3) {
				// add "visible" property
				// if( !array_key_exists('visible', $item3) ) {
				// 	$item3['visible'] = true;
				// }
				$item3 = (object) $item3;
			}
			$json = json_encode($sortedTagsArrayReturn);
			wp_send_json($json);
		} else {
			$json = json_encode($tags);
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
		if ($cmp > 0) {
			$value = 1;
		} elseif ($cmp < 0) {
			$value = -1;
		}
		return $value;
	}

	public static function usortTag($a, $b) {
		// if ($a['tags_markup'] == $b['tags_markup']) {
		//     return 0;
		// }
		// $cmp = strcmp($a['tags_markup'], $b['tags_markup']);
		// $value = 0;
		// if( $cmp > 0 ) {
		//     $value = 1;
		// } else if ( $cmp < 0 ) {
		//     $value = -1;
		// }
		// return $value;

		// https://stackoverflow.com/questions/3699439/natsort-multidemsional-array
		// using natsort so numbers will be sorted correctly too, in case someone uses numbers for tags
		return strnatcasecmp($a['tags_markup'], $b['tags_markup']);
	}

	public static function usortTitle($a, $b) {
		if ($a['post_title'] == $b['post_title']) {
			return 0;
		}
		$cmp = strcmp($a['post_title'], $b['post_title']);
		$value = 0;
		if ($cmp > 0) {
			$value = 1;
		} elseif ($cmp < 0) {
			$value = -1;
		}
		return $value;
	}

	public static function usortDate($a, $b) {
		$a_date = self::get_projectindex_row_date_ymd($a['post_id']);
		$b_date = self::get_projectindex_row_date_ymd($b['post_id']);
		if ($a_date == $b_date) {
			return 0;
		}

		return strtotime($a_date) - strtotime($b_date);
	}

	public static function orderRows(&$rows, $orderby, $order) {
		switch ($orderby) {
			case 'categories':
				usort($rows, 'ProjectIndex::usortCat');
				break;
			case 'tags':
				usort($rows, 'ProjectIndex::usortTag');
				break;
			case 'title':
				usort($rows, 'ProjectIndex::usortTitle');
				break;
			case 'date':
				usort($rows, 'ProjectIndex::usortDate');
				break;
		}

		if ($order == 'DESC') {
			$rows = array_reverse($rows);
		}
	}

	public static function get_category_list_for_projectindex() {
		$sortedCategoriesArray = array_key_exists('categories', $_POST) ? $_POST['categories'] : null;
		$categories = get_categories(['hide_empty' => false, 'orderby' => 'menu_order']);

		if (is_array($sortedCategoriesArray)) {
			// first see if we have any ids in $categories that are not in $sortedCategoriesArray
			// in that case user must have added new categories
			$sortedCatIds = [];
			$categoryIds = [];
			foreach ($categories as $category) {
				$categoryIds[] = $category->term_id;
			}
			foreach ($sortedCategoriesArray as $item) {
				$sortedCatIds[] = $item['term_id'];
			}
			// check if there are new cat ids
			foreach ($categoryIds as $catId) {
				// a category id is not in sortedCatIds! append it
				if (!in_array($catId, $sortedCatIds)) {
					$sortedCategoriesArray[] = ['term_id' => $catId, 'visible' => true];
				}
			}

			// also update names in case category names were changed
			foreach ($categories as $category) {
				foreach ($sortedCategoriesArray as &$item) {
					if ($item['term_id'] == $category->term_id) {
						$item['name'] = $category->name;
						$item['name_display'] = $category->name;
						break;
					}
				}
			}

			// and only put in categories that still exist
			$sortedCategoriesArrayReturn = [];
			foreach ($sortedCategoriesArray as $item2) {
				foreach ($categories as $category) {
					if ($item2['term_id'] == $category->term_id) {
						$sortedCategoriesArrayReturn[] = $item2;
					}
				}
			}
			// now convert to objects
			foreach ($sortedCategoriesArrayReturn as &$item3) {
				if (!isset($item3['name_display'])) {
					$item3['name_display'] = $item3['name'];
				}
				$item3 = (object) $item3;
			}
			$json = json_encode($sortedCategoriesArrayReturn);
			wp_send_json($json);
		} else {
			// Add name_display (same as name) for compatibility with modal
			$categories_with_display = [];
			foreach ($categories as $category) {
				$categories_with_display[] = (object) [
					'term_id' => $category->term_id,
					'name' => $category->name,
					'name_display' => $category->name,
				];
			}
			$json = json_encode($categories_with_display);
			wp_send_json($json);
		}
		die();
	}

	public static function get_projectindex_via_ajax() {
		$config = $_POST['config'];
		$uniqueId = array_key_exists('uniqueId', $_POST) ? $_POST['uniqueId'] : '';
		$currentLayout = array_key_exists('currentLayout', $_POST) ? $_POST['currentLayout'] : 'desktop';
		echo ProjectIndex::get_projectindex($config, $currentLayout);
		die();
	}

	public static function get_projectindex($config, $currentLayout, $relid = '') {
		$orderby = '';
		if (is_array($config)) {
			$orderby_temp = $config['orderBy'];
			$order = $config['order'];
		}
		// when order by is category or tag, i need to order manually, cause wordpress doesnt accept these arguments for order
		if ($orderby_temp != 'category' && $orderby_temp != 'tag') {
			$orderby = $orderby_temp;
		}
		// only the projects of these categories are shown. empty for all. this is the value of the multiselector for categories in the modal

		// i have no idea why, but orderby and order doesnt work here
		// $query->request always returns "wp_posts.menu_order ASC" even if i use totally different values
		// thats why i just sort myself by using "ProjectIndex::orderRows"
		/*
        
        wp_posts.ID FROM wp_posts  WHERE 1=1  AND wp_posts.post_type = 'post' AND ((wp_posts.post_status = 'publish'))  ORDER BY wp_posts.menu_order ASC 


        */
		$query_args = [
			'post_status' => 'publish',
			'orderby' => $orderby,
			'order' => $order,
			'posts_per_page' => -1,
			'fields' => 'ids',
		];

		// Only exclude password protected posts if the setting is disabled
		if (LayFrontend_Options::$show_password_protected_posts_in_projectindex == false) {
			$query_args['has_password'] = false;
		}

		$category_term_slugs = [];
		if (is_array($config['category'])) {
			foreach ($config['category'] as $cat) {
				$category_term_slugs[] = $cat['value'];
			}
		}
		if (count($category_term_slugs) > 0) {
			$query_args['category_name'] = implode(',', $category_term_slugs);
		}
		$style = array_key_exists('style', $config) ? $config['style'] : 'table';
		$separator = $style == 'comma' || $style == 'comma2' ? '<span class="lay-pi-separator">,&nbsp;</span>' : '';
		$separator2 = $style == 'comma' || $style == 'comma2' ? '<span class="_' . $config['filtersTextformat'] . '_no_spaces lay-pi-separator">,&nbsp;</span>' : '';

		// 'color:'.$config['textColor'].';';

		$uniqueId = array_key_exists('uniqueId', $config) ? $config['uniqueId'] : '';
		// Use relid if provided (for frontend elements), otherwise fall back to uniqueId (for AJAX calls)
		$elementId = !empty($relid) ? $relid : $uniqueId;
		$filterColor = array_key_exists('filterColor', $config) ? $config['filterColor'] : $config['textColor'];
		$filterHoverColor = array_key_exists('filterHoverColor', $config) ? $config['filterHoverColor'] : $filterColor;

		$filterHoverCSS = '';
		if (array_key_exists('doFilterHoverColor', $config) && $config['doFilterHoverColor'] == true) {
			$filterHoverCSS =
				'
                .lay-projectindex.id-' .
				$elementId .
				' .lay-projectindex-filter-inner:hover polyline{ stroke:' .
				$filterHoverColor .
				'; }
                .lay-projectindex.id-' .
				$elementId .
				' .lay-projectindex-filter-inner:hover{ color:' .
				$filterHoverColor .
				'; }
                .lay-projectindex.id-' .
				$elementId .
				' .lay-projectindex-filter-inner:hover span{ color:' .
				$filterHoverColor .
				'; }
            ';
		}

		$paddingDesktop = array_key_exists('paddingLeftRight', $config['desktop']) ? $config['desktop']['paddingLeftRight'] : 0;
		$paddingTablet = array_key_exists('paddingLeftRight', $config['tablet']) ? $config['tablet']['paddingLeftRight'] : 0;
		$paddingPhone = array_key_exists('paddingLeftRight', $config['phone']) ? $config['phone']['paddingLeftRight'] : 0;

		$phone_breakpoint = get_option('lay_breakpoint', 600);
		$phone_breakpoint = (int) $phone_breakpoint;

		$tablet_breakpoint = get_option('lay_tablet_breakpoint', 1024);
		$tablet_breakpoint = (int) $tablet_breakpoint;

		$date_format = array_key_exists('date_format', $config) ? $config['date_format'] : 'Y';

		$doActiveColor = array_key_exists('doActiveColor', $config) ? $config['doActiveColor'] : 'true';
		$doActiveColor = $doActiveColor == 'true' ? true : false;

		$activeCSS = '.lay-projectindex.id-' . $elementId . ' .lay-project-index-current-project{ background-color:' . $config['activeColor'] . '; }';
		if ($doActiveColor == false) {
			$activeCSS = '';
		}

		// when imagehover addon is active, we add a hover class by javascript
		// cause with the imagehover addon we trigger a "mouseover" or simulate a mouseover even when we just scroll and dont move the mouse
		// that is why here we do: .lay-projectindex.lay-projectindex-imagehover-not-active for some of the hover css
		$selector = '.lay-projectindex.id-' . $elementId;
		
		$hoverColorCSS = '';
		if (array_key_exists('doHoverColor', $config) && $config['doHoverColor'] == true) {
			$hoverColorCSS = $selector . '.lay-projectindex-imagehover-not-active .lay-projectindex-row:hover>div, ' .
				$selector . '.lay-projectindex-imagehover-active .lay-projectindex-row.hover>div{ color:' . $config['hoverColor'] . '; }';
		}
		
		$bgHoverColorCSS = '';
		if (array_key_exists('doBgHoverColor', $config) && $config['doBgHoverColor'] == true) {
			$bgHoverColorCSS = $selector . '.lay-projectindex-imagehover-not-active .lay-projectindex-row:hover, ' .
				$selector . '.lay-projectindex-imagehover-active .lay-projectindex-row.hover{ background-color:' . $config['bgHoverColor'] . '; }';
		}
		
		$projectindex_css = '<style>
			@media (min-width: ' . ($tablet_breakpoint + 1) . 'px) {
				' . $selector . ' .lay-projectindex-row,
				' . $selector . ' .lay-projectindex-filter-wrap {
					padding-left: ' . $paddingDesktop . 'px;
					padding-right: ' . $paddingDesktop . 'px;
				}
			}
			@media (max-width: ' . $tablet_breakpoint . 'px) and (min-width: ' . ($phone_breakpoint + 1) . 'px) {
				' . $selector . ' .lay-projectindex-row,
				' . $selector . ' .lay-projectindex-filter-wrap {
					padding-left: ' . $paddingTablet . 'px;
					padding-right: ' . $paddingTablet . 'px;
				}
			}
			@media (max-width: ' . $phone_breakpoint . 'px) {
				' . $selector . ' .lay-projectindex-row,
				' . $selector . ' .lay-projectindex-filter-wrap {
					padding-left: ' . $paddingPhone . 'px;
					padding-right: ' . $paddingPhone . 'px;
				}
			}
			' . $activeCSS . '
			' . $selector . ' .lay-projectindex-filter-inner polyline { stroke: ' . $filterColor . '; }
			' . $selector . ' .lay-projectindex-filter-inner { color: ' . $filterColor . '; }
			' . $selector . ' .lay-projectindex-filter-inner span { color: ' . $filterColor . ' !important; }
			' . $filterHoverCSS . '
			' . $selector . ' .lay-projectindex-col { color: ' . $config['textColor'] . '; }
			' . $hoverColorCSS . '
			' . $bgHoverColorCSS . '
		</style>';

		$imagehover_active = $config['useImageHover'] && is_plugin_active('laytheme-imagehover/laytheme-imagehover.php') == true ? 'lay-projectindex-imagehover-active' : 'lay-projectindex-imagehover-not-active';
		$imagehover_videos_active = array_key_exists('useImageHoverVideos', $config) && $config['useImageHoverVideos'] && is_plugin_active('laytheme-imagehover/laytheme-imagehover.php') == true ? 'lay-projectindex-imagehover-videos-active' : 'lay-projectindex-imagehover-videos-not-active';

		$lay_projectindex = '<div class="lay-projectindex lay-projectindex-style-' . $style . ' id-' . $elementId . ' ' . $imagehover_active . '" data-config="' . htmlspecialchars(json_encode($config)) . '">';

		// filter!

		$showFilter = ($config['showFilters'] === 'true' || $config['showFilters'] === true) ? true : false;

		if ($showFilter === true) {
			$lay_projectindex .= '<div class="lay-projectindex-filter-wrap">';

			foreach ($config[$currentLayout]['sortedColumns'] as $column) {
				$css = 'padding-top:' . $config['spaceTopBottom'] . 'px; padding-bottom:' . $config['spaceTopBottom'] . 'px;';
				if ($column['visible'] === 'false' || $column['visible'] === false) {
					$css .= 'display:none;';
				}

			// Map column names to their filter config keys and orderby values
			$filterConfig = [
				'year' => [
					'filterTextKey' => 'yearFilterText',
					'orderbyValue' => 'date',
					'filterClass' => 'lay-projectindex-filter-year',
				],
				'title' => [
					'filterTextKey' => 'titleFilterText',
					'orderbyValue' => 'title',
					'filterClass' => 'lay-projectindex-filter-title',
				],
				'categories' => [
					'filterTextKey' => 'categoriesFilterText',
					'orderbyValue' => 'categories',
					'filterClass' => 'lay-projectindex-filter-categories',
				],
				'tags' => [
					'filterTextKey' => 'tagsFilterText',
					'orderbyValue' => 'tags',
					'filterClass' => 'lay-projectindex-filter-tags',
				],
			];

			if (isset($filterConfig[$column['name']])) {
				$filter = $filterConfig[$column['name']];
				$active = ($orderby == $filter['orderbyValue']) ? 'projectindex-filter-active' : '';
				$filterText = $config[$filter['filterTextKey']];
				$textformatClass = '_' . $config['filtersTextformat'] . '_no_spaces';
				$svgIcon = '<svg height="9" width="16"><polyline fill="none" stroke="black" points="1,0 8,8 15,0"></polyline></svg>';

				$lay_projectindex .= '<div class="' . $active . ' order-' . $order . ' lay-projectindex-filter ' . $filter['filterClass'] . '" style="' . $css . '">
					<div class="lay-projectindex-filter-inner">
						<span class="' . $textformatClass . '">' . $filterText . '</span>
						' . $svgIcon . '
						' . $separator2 . '
					</div>
				</div>';
			}
			}

			$lay_projectindex .= '</div>';
		}
		// #filter

		$rows = [];

		$query = new WP_Query($query_args);

		$current_post_id = false;
		global $post;
		if (is_object($post)) {
			$current_post_id = $post->ID;
		}

		if ($query->have_posts()) {
			foreach ($query->posts as $id) {
				// title
				// categories
				// tags
				// year
				$cat_names = wp_get_post_categories($id, ['fields' => 'names', 'orderby' => 'term_order']);
				$cat_ids = wp_get_post_categories($id, ['fields' => 'ids', 'orderby' => 'term_order']);

				// order tags by user order:
				if (array_key_exists('sortedCategories', $config) && is_array($config['sortedCategories'])) {
					// Match by term_id so child categories with the same name under different parents are not duplicated.
					$ordered_result_cats = [];
					$added_term_ids = [];
					$hidden_term_ids = [];

					foreach ($config['sortedCategories'] as $ordered_cat) {
						$v = $ordered_cat['visible'];

						if (gettype($v) == 'string') {
							if ($ordered_cat['visible'] === 'false' || $ordered_cat['visible'] === false) {
								$v = false;
							} elseif ($ordered_cat['visible'] === 'true' || $ordered_cat['visible'] === true) {
								$v = true;
							}
						}

						$term_id = (int) $ordered_cat['term_id'];
						$is_assigned = in_array($term_id, $cat_ids, true);

						if ($v == true) {
							if ($is_assigned) {
								$term = get_term_by('term_id', $term_id, 'category');
								if ($term !== false) {
									$ordered_result_cats[] = $term->name;
									$added_term_ids[] = $term_id;
								}
							}
						} else {
							if ($is_assigned) {
								$hidden_term_ids[] = $term_id;
							}
						}
					}

					$cats_to_add = [];
					foreach ($cat_ids as $cid) {
						$cid = (int) $cid;
						if (in_array($cid, $added_term_ids, true) || in_array($cid, $hidden_term_ids, true)) {
							continue;
						}
						$term = get_term_by('term_id', $cid, 'category');
						if ($term !== false) {
							$cats_to_add[] = $term->name;
						}
					}

					$cat_names = array_merge($ordered_result_cats, $cats_to_add);
				}

				$categoriesSeparator = array_key_exists('categoriesSeparator', $config) ? $config['categoriesSeparator'] : ', ';
				$cats_markup = implode($categoriesSeparator, $cat_names);

				$tags = get_the_tags($id);

				$tag_names = [];
				// order by term order, and fill $tag_names with names of tags
				if ($tags) {
					// https://stackoverflow.com/questions/2699086/how-to-sort-a-multi-dimensional-array-by-value
					usort($tags, function ($a, $b) {
						return $a->term_order - $b->term_order;
					});

					foreach ($tags as $tag) {
						$tag_names[] = $tag->name;
					}
				}

				// order by user defined order
				if (array_key_exists('sortedTags', $config) && is_array($config['sortedTags']) && $tags) {
					// $tags contains all tags of project.
					// now traverese through user ordered tags to order these project tags and hide/show them based on "visible" value
					$ordered_result_tags = [];
					foreach ($config['sortedTags'] as $ordered_tag) {
						$v = $ordered_tag['visible'];
						if (gettype($v) == 'string') {
							if ($ordered_tag['visible'] == 'false') {
								$v = false;
							} elseif ($ordered_tag['visible'] == 'true') {
								$v = true;
							}
						}
						if ($v == true) {
							// get tag name by code, in case we switched to a different language or tag name was changed
							$term = get_term_by('term_id', $ordered_tag['term_id'], 'post_tag');
							if ($term !== false) {
								if (array_search($term->name, $tag_names) !== false) {
									// get term name by code, this way the name is updated when switching btwn languages or changing term name
									$ordered_result_tags[] = $term->name;
								}
							}
						}
					}

					$tags_to_add = [];
					// we have new categories that are not yet added to ordered categories?
					if (count($tag_names) > count($ordered_result_tags)) {
						foreach ($tag_names as $name) {
							if (array_search($name, $ordered_result_tags) === false) {
								$tags_to_add[] = $name;
							}
						}
					}

					$tag_names = array_merge($ordered_result_tags, $tags_to_add);
				}

				$tagsSeparator = array_key_exists('tagsSeparator', $config) ? $config['tagsSeparator'] : ', ';
				$tags_markup = implode($tagsSeparator, $tag_names);
				/*
                    'sortedCategories' => false,
                    'sortedTags' => false,
                    'textformat' => 'Default',
                    'spaceTopBottom' => 5,
                    'lineStrokeWidth' => 1,
                    'lineColor' => '#000',
                    'useImageHover' => true
                */

				// put all post related things in an array so we can sort by category or tags
				// without having to get the categories and tags inside the sort function
				$project_link = Setup::get_project_link($id);

				$rows[] = [
					'post_id' => $id,
					'cat_ids' => $cat_ids,
					'post_link' => $project_link['url'],
					'post_link_target' => $project_link['target'],
					'post_link_disabled' => $project_link['disabled'],
					'post_name' => get_post_field('post_name', $id),
					'post_title' => get_the_title($id),
					'post_date' => self::get_projectindex_row_year_display($id, $date_format),
					'cats_markup' => $cats_markup,
					'tags_markup' => $tags_markup,
				];
			}

			// sort!

			ProjectIndex::orderRows($rows, $orderby, $order);
			// iterate
			$ix = 0;
			foreach ($rows as $row) {
				$border_style = 'border-bottom:' . $config['lineStrokeWidth'] . 'px solid ' . $config['lineColor'] . ';';
				$extraclass = '';
				if ($ix == 0) {
					$extraclass = 'lay-projectindex-first-row';
					$border_style .= 'border-top:' . $config['lineStrokeWidth'] . 'px solid ' . $config['lineColor'] . ';';
				}
				// $font_color_css = '';
				// if( trim($config['textColor']) != '' ) {
				//     $font_color_css = 'color:'.$config['textColor'].';';
				// }
				$current_post_class = $current_post_id == $row['post_id'] ? 'lay-project-index-current-project' : '';
				if (LayFrontend_Options::$hide_current_project_from_thumbnailgrid == 'on' && $current_post_id == $row['post_id']) {
					continue;
				}
				
				// Check if link is disabled (from get_project_link)
				$link_disabled_class = $row['post_link_disabled'] ? 'link-disabled' : '';

				$lay_projectindex .= '<div class="lay-projectindex-row ' . $current_post_class . ' projectindex-project-slug-' . $row['post_name'] . ' _' . $config['textformat'] . '_no_spaces ' . $extraclass . ' ' . $link_disabled_class . '" style="' . $border_style . ' padding-top:' . $config['spaceTopBottom'] . 'px; padding-bottom:' . $config['spaceTopBottom'] . 'px;">';

				// $ix2 = 0;
				foreach ($config[$currentLayout]['sortedColumns'] as $column) {
					$css = '';
					if ($column['visible'] === 'false' || $column['visible'] === false) {
						$css = 'style="display:none;"';
					}
					// if( $ix2 == count($config[$currentLayout]['sortedColumns']) - 1 ) {
					//     $separator = '';
					// }
					switch ($column['name']) {
						case 'year':
							$lay_projectindex .= '<div class="lay-projectindex-col-year lay-projectindex-col" data-date="' . esc_attr(self::get_projectindex_row_date_ymd($row['post_id'])) . '" ' . $css . '>' . $row['post_date'] . $separator . '</div>';
							break;
						case 'title':
							$lay_projectindex .= '<div class="lay-projectindex-col-title lay-projectindex-col" ' . $css . '>' . $row['post_title'] . $separator . '</div>';
							break;
						case 'categories':
							$lay_projectindex .= '<div class="lay-projectindex-col-categories lay-projectindex-col" ' . $css . '>' . $row['cats_markup'] . $separator . '</div>';
							break;
						case 'tags':
							$emptyClass = $row['tags_markup'] == '' ? 'lay-projectindex-col-empty' : '';
							$lay_projectindex .= '<div class="lay-projectindex-col-tags lay-projectindex-col ' . $emptyClass . '" ' . $css . '>' . $row['tags_markup'] . $separator . '</div>';
							break;
					}
					// $ix2++;
				}

				$imagehover_attr = '';
				if ($config['useImageHover'] == true && is_plugin_active('laytheme-imagehover/laytheme-imagehover.php')) {
					// video thumbnail maybe!
					$video_id = '';
					if (VideoThumbnails::$active == true) {
						$video_id = get_post_meta($row['post_id'], '_lay_thumbnail_video', true);
					}
					if (array_key_exists('useImageHoverVideos', $config) && $config['useImageHoverVideos'] == true && $video_id != -1 && $video_id != '') {
						$imagehover_attr = 'data-hoverimageid="' . $video_id . '" data-hoverimagetype="video"';
					} else {
						$imagehover_attr = 'data-hoverimageid="' . get_post_thumbnail_id($row['post_id']) . '"';
					}
				}

				$link_tag = $row['post_link_disabled'] ? 'span' : 'a';
				$href = '';
				if (!$row['post_link_disabled']) {
					$href = 'href="' . esc_attr($row['post_link']) . '"';
					if ($row['post_link_target'] !== '') {
						$href .= ' target="' . esc_attr($row['post_link_target']) . '" rel="noopener"';
					}
				}

				$lay_projectindex .= '<' . $link_tag . ' class="projectindex-project-link" ' . $href . '  ' . $imagehover_attr . ' data-type="project" data-catid="' . LayElFunctions::stringifyCatIds($row['cat_ids']) . '" data-id="' . $row['post_id'] . '" data-title="' . __($row['post_title']) . '" data-slug="' . $row['post_name'] . '"></' . $link_tag . '>';
				$lay_projectindex .= '</div>';
				$ix++;
			}
		}
		$lay_projectindex .= '</div>';

		return $lay_projectindex . $projectindex_css;
	}
}
new ProjectIndex();
