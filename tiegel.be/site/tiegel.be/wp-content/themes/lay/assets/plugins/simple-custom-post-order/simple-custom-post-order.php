<?php

/*
  Plugin Name: Simple Custom Post Order
  Plugin URI: http://hsameer.com.np/simple-custom-post-order/
  Description: Order Items (Posts, Pages, and Custom Post Types) using a Drag and Drop Sortable JavaScript.
  Version: 2.3.1
  Author: Sameer Humagain
  Author URI: http://hsameer.com.np/
 */


 define('SCPORDER_URL', get_template_directory_uri().'/assets/plugins/simple-custom-post-order');
 define('SCPORDER_DIR', get_template_directory().'/assets/plugins/simple-custom-post-order/');

$scporder = new SCPO_Engine();

class SCPO_Engine {

    function __construct() {
        if (!get_option('scporder_install'))
            $this->scporder_install();

        add_action('admin_init', array($this, 'refresh'));

        add_action('admin_init', array($this, 'update_options'));
        add_action('admin_init', array($this, 'load_script_css'));

        add_action('wp_ajax_update-menu-order', array($this, 'update_menu_order'));
        add_action('wp_ajax_update-menu-order-tags', array($this, 'update_menu_order_tags'));

        add_action('pre_get_posts', array($this, 'scporder_pre_get_posts'));

        add_filter('get_previous_post_where', array($this, 'scporder_previous_post_where'));
        add_filter('get_previous_post_sort', array($this, 'scporder_previous_post_sort'));
        add_filter('get_next_post_where', array($this, 'scporder_next_post_where'));
        add_filter('get_next_post_sort', array($this, 'scporder_next_post_sort'));

        add_filter('get_terms_orderby', array($this, 'scporder_get_terms_orderby'), 10, 3);
        add_filter('wp_get_object_terms', array($this, 'scporder_get_object_terms'), 10, 3);
        add_filter('get_terms', array($this, 'scporder_get_object_terms'), 10, 3);
    }

    function scporder_install() {
        global $wpdb;
        $result = $wpdb->query("DESCRIBE $wpdb->terms `term_order`");
        if (!$result) {
            $query = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
            $result = $wpdb->query($query);
        }
        update_option('scporder_install', 1);
    }

    function _check_load_script_css() {
        $active = false;

        $objects = $this->get_scporder_options_objects();
        $tags = $this->get_scporder_options_tags();

        if (empty($objects) && empty($tags))
            return false;

        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (isset($_GET['orderby']) || strstr($request_uri, 'action=edit') || strstr($request_uri, 'wp-admin/post-new.php'))
            return false;

        if (!empty($objects)) {
            if (isset($_GET['post_type']) && !isset($_GET['taxonomy']) && in_array($_GET['post_type'], $objects)) { // if page or custom post types
                $active = true;
            }
            if (!isset($_GET['post_type']) && strstr($request_uri, 'wp-admin/edit.php') && in_array('post', $objects)) { // if post
                $active = true;
            }
        }

        if (!empty($tags)) {
            if (isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], $tags)) {
                $active = true;
            }
        }

        return $active;
    }

    function load_script_css() {
        if ($this->_check_load_script_css()) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('scporderjs', SCPORDER_URL . '/assets/scporder.js', array('jquery'), null, true);
            wp_localize_script('scporderjs', 'scporder_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scporder_nonce_action'),
            ));
            wp_enqueue_style('scporder', SCPORDER_URL . '/assets/scporder.css', array(), null);
        }
    }

    function refresh() {
        if (scporder_doing_ajax()) {
            return;
        }
        global $wpdb;
        $objects = $this->get_scporder_options_objects();
        $tags = $this->get_scporder_options_tags();

        if (!empty($objects)) {
            foreach ($objects as $object) {
                $object = sanitize_key($object);
                $result = $wpdb->get_results($wpdb->prepare("
					SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min
					FROM $wpdb->posts
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
				", $object));
                if (!$result || $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max)
                    continue;

                $results = $wpdb->get_results($wpdb->prepare("
					SELECT ID
					FROM $wpdb->posts
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					ORDER BY menu_order ASC
				", $object));
                if ($results) {
                    foreach ($results as $key => $row) {
                        $wpdb->update($wpdb->posts, array('menu_order' => $key + 1), array('ID' => $row->ID), array('%d'), array('%d'));
                    }
                }
            }
        }

        if (!empty($tags)) {
            foreach ($tags as $taxonomy) {
                $taxonomy = sanitize_key($taxonomy);
                $result = $wpdb->get_results($wpdb->prepare("
					SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s
				", $taxonomy));
                if (!$result || $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max)
                    continue;

                $results = $wpdb->get_results($wpdb->prepare("
					SELECT terms.term_id
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s
					ORDER BY term_order ASC
				", $taxonomy));
                if ($results) {
                    foreach ($results as $key => $row) {
                        $wpdb->update($wpdb->terms, array('term_order' => $key + 1), array('term_id' => $row->term_id), array('%d'), array('%d'));
                    }
                }
            }
        }
    }

    function update_menu_order() {
        global $wpdb;

        check_ajax_referer('scporder_nonce_action', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'), 403);
        }

        $order = isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : '';
        parse_str($order, $data);

        if (!is_array($data) || empty($data)) {
            wp_send_json_error(array('message' => 'Invalid data.'));
        }

        $id_arr = array();
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $position => $id) {
                    $id_arr[] = absint($id);
                }
            }
        }

        $menu_order_arr = array();
        foreach ($id_arr as $id) {
            $menu_order = $wpdb->get_var($wpdb->prepare("SELECT menu_order FROM $wpdb->posts WHERE ID = %d", $id));
            if (null !== $menu_order) {
                $menu_order_arr[] = (int) $menu_order;
            }
        }
        sort($menu_order_arr);

        $updated_ids = array();
        $position = 0;
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $id) {
                    $id = absint($id);
                    if (isset($menu_order_arr[$position])) {
                        $wpdb->update(
                            $wpdb->posts,
                            array('menu_order' => $menu_order_arr[$position]),
                            array('ID' => $id),
                            array('%d'),
                            array('%d')
                        );
                        $updated_ids[] = $id;
                    }
                    $position++;
                }
            }
        }
        foreach ($updated_ids as $post_id) {
            clean_post_cache($post_id);
        }
        wp_send_json_success(array('message' => 'Order updated.'));
    }

    function update_menu_order_tags() {
        global $wpdb;

        check_ajax_referer('scporder_nonce_action', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'), 403);
        }

        $order = isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : '';
        parse_str($order, $data);

        if (!is_array($data) || empty($data)) {
            wp_send_json_error(array('message' => 'Invalid data.'));
        }

        $id_arr = array();
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $position => $id) {
                    $id_arr[] = absint($id);
                }
            }
        }

        $term_order_arr = array();
        foreach ($id_arr as $id) {
            $term_order = $wpdb->get_var($wpdb->prepare("SELECT term_order FROM $wpdb->terms WHERE term_id = %d", $id));
            if (null !== $term_order) {
                $term_order_arr[] = (int) $term_order;
            }
        }
        sort($term_order_arr);

        $updated_ids = array();
        $position = 0;
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $id) {
                    $id = absint($id);
                    if (isset($term_order_arr[$position])) {
                        $wpdb->update(
                            $wpdb->terms,
                            array('term_order' => $term_order_arr[$position]),
                            array('term_id' => $id),
                            array('%d'),
                            array('%d')
                        );
                        $updated_ids[] = $id;
                    }
                    $position++;
                }
            }
        }
        foreach ($updated_ids as $term_id) {
            clean_term_cache($term_id);
        }
        wp_send_json_success(array('message' => 'Order updated.'));
    }

    function update_options() {
        global $wpdb;

        if (!isset($_POST['scporder_submit']))
            return false;

        check_admin_referer('nonce_scporder');

        $input_options = array();
        $input_options['objects'] = isset($_POST['objects']) ? $_POST['objects'] : '';
        $input_options['tags'] = isset($_POST['tags']) ? $_POST['tags'] : '';

        update_option('scporder_options', $input_options);

        $objects = $this->get_scporder_options_objects();
        $tags = $this->get_scporder_options_tags();

        if (!empty($objects)) {
            foreach ($objects as $object) {
                $result = $wpdb->get_results("
					SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min
					FROM $wpdb->posts
					WHERE post_type = '" . $object . "' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
				");
                if ($result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max)
                    continue;

                if ($object == 'page') {
                    $results = $wpdb->get_results("
						SELECT ID
						FROM $wpdb->posts
						WHERE post_type = '" . $object . "' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
						ORDER BY post_title ASC
					");
                } else {
                    $results = $wpdb->get_results("
						SELECT ID
						FROM $wpdb->posts
						WHERE post_type = '" . $object . "' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
						ORDER BY post_date DESC
					");
                }
                foreach ($results as $key => $result) {
                    $wpdb->update($wpdb->posts, array('menu_order' => $key + 1), array('ID' => $result->ID));
                }
            }
        }

        if (!empty($tags)) {
            foreach ($tags as $taxonomy) {
                $result = $wpdb->get_results("
					SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = '" . $taxonomy . "'
				");
                if ($result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max)
                    continue;

                $results = $wpdb->get_results("
					SELECT terms.term_id
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = '" . $taxonomy . "'
					ORDER BY name ASC
				");
                foreach ($results as $key => $result) {
                    $wpdb->update($wpdb->terms, array('term_order' => $key + 1), array('term_id' => $result->term_id));
                }
            }
        }

        wp_redirect('admin.php?page=scporder-settings&msg=update');
    }

    function scporder_previous_post_where($where) {
        global $post;

        $objects = $this->get_scporder_options_objects();
        if (empty($objects))
            return $where;

        if (isset($post->post_type) && in_array($post->post_type, $objects)) {
            $current_menu_order = $post->menu_order;
            $where = "WHERE p.menu_order > '" . $current_menu_order . "' AND p.post_type = '" . $post->post_type . "' AND p.post_status = 'publish'";
        }
        return $where;
    }

    function scporder_previous_post_sort($orderby) {
        global $post;

        $objects = $this->get_scporder_options_objects();
        if (empty($objects))
            return $orderby;

        if (isset($post->post_type) && in_array($post->post_type, $objects)) {
            $orderby = 'ORDER BY p.menu_order ASC LIMIT 1';
        }
        return $orderby;
    }

    function scporder_next_post_where($where) {
        global $post;

        $objects = $this->get_scporder_options_objects();
        if (empty($objects))
            return $where;

        if (isset($post->post_type) && in_array($post->post_type, $objects)) {
            $current_menu_order = $post->menu_order;
            $where = "WHERE p.menu_order < '" . $current_menu_order . "' AND p.post_type = '" . $post->post_type . "' AND p.post_status = 'publish'";
        }
        return $where;
    }

    function scporder_next_post_sort($orderby) {
        global $post;

        $objects = $this->get_scporder_options_objects();
        if (empty($objects))
            return $orderby;

        if (isset($post->post_type) && in_array($post->post_type, $objects)) {
            $orderby = 'ORDER BY p.menu_order DESC LIMIT 1';
        }
        return $orderby;
    }

    function scporder_pre_get_posts($wp_query) {
        $objects = $this->get_scporder_options_objects();
        if (empty($objects)) {
            return;
        }
        if (is_search()) {
            return;
        }
        if (is_admin() && !wp_doing_ajax()) {
            $post_type = isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : 'post';
            if (isset($wp_query->query['post_type']) && in_array($post_type, $objects)) {
                // Always force menu_order so custom order persists after refresh (official 2.6.0 only applies when $_GET['orderby'] is not set, so reload can show date order).
                $wp_query->set('orderby', 'menu_order');
                $wp_query->set('order', 'ASC');
            }
        } else {
            $active = false;
            if (isset($wp_query->query['post_type'])) {
                if (!is_array($wp_query->query['post_type']) && in_array($wp_query->query['post_type'], $objects)) {
                    $active = true;
                }
            } elseif (in_array('post', $objects)) {
                $active = true;
            }
            if (!$active) {
                return;
            }
            if (isset($wp_query->query['suppress_filters'])) {
                if ($wp_query->get('orderby') === 'date') {
                    $wp_query->set('orderby', 'menu_order');
                }
                if ($wp_query->get('order') === 'DESC') {
                    $wp_query->set('order', 'ASC');
                }
            } else {
                if (!$wp_query->get('orderby')) {
                    $wp_query->set('orderby', 'menu_order');
                }
                if (!$wp_query->get('order')) {
                    $wp_query->set('order', 'ASC');
                }
            }
        }
    }

    function scporder_get_terms_orderby($orderby, $args) {
        if (is_admin())
            return $orderby;

        $tags = $this->get_scporder_options_tags();

        if (!isset($args['taxonomy']))
            return $orderby;

        $taxonomy = $args['taxonomy'];
        if (!in_array($taxonomy, $tags))
            return $orderby;

        $orderby = 't.term_order';
        return $orderby;
    }

    function scporder_get_object_terms($terms) {
        $tags = $this->get_scporder_options_tags();

        if (is_admin() && isset($_GET['orderby']))
            return $terms;

        foreach ($terms as $key => $term) {
            if (is_object($term) && isset($term->taxonomy)) {
                $taxonomy = $term->taxonomy;
                if (!in_array($taxonomy, $tags))
                    return $terms;
            } else {
                return $terms;
            }
        }

        usort($terms, array($this, 'taxcmp'));
        return $terms;
    }

    function taxcmp($a, $b) {
        if ($a->term_order == $b->term_order)
            return 0;
        return ( $a->term_order < $b->term_order ) ? -1 : 1;
    }

    function get_scporder_options_objects() {
        // $scporder_options = get_option('scporder_options') ? get_option('scporder_options') : array();
        // $objects = isset($scporder_options['objects']) && is_array($scporder_options['objects']) ? $scporder_options['objects'] : array();

        $custom_post_types = get_post_types(
            array(
                'public'   => true,   // Only get public post types
                '_builtin' => false,  // Exclude built-in post types like 'post' and 'page'
            ),
            'names' // Return only the names of the post types
        );

        $builtin = array('post', 'page');
        $objects = array_merge($builtin, $custom_post_types);
        return $objects;
        // return array('post', 'page', 'lay_news');
    }

    // function get_scporder_options_tags() {
    //     // $scporder_options = get_option('scporder_options') ? get_option('scporder_options') : array();
    //     // $tags = isset($scporder_options['tags']) && is_array($scporder_options['tags']) ? $scporder_options['tags'] : array();
       
    //     // return $tags;
    //     return array('post_tag', 'category');
    // }


    function get_scporder_options_tags() {
        // $scporder_options = get_option('scporder_options') ? get_option('scporder_options') : array();
        // $tags = isset($scporder_options['tags']) && is_array($scporder_options['tags']) ? $scporder_options['tags'] : array();

        $custom_taxonomies = get_taxonomies(array(
            'public' => true, // Only get public taxonomies
            '_builtin' => false // Exclude built-in taxonomies like category and post_tag
        ), 'names');

        $builtin = array('post_tag', 'category');

        $tags = array_merge($builtin, $custom_taxonomies);
        return $tags;
    }

}

function scporder_doing_ajax() {
    if (function_exists('wp_doing_ajax')) {
        return wp_doing_ajax();
    }
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return true;
    }
    return false;
}
