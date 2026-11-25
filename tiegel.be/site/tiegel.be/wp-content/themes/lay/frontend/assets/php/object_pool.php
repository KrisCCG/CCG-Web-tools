<?php

/**
 * Object Pool for performance optimization
 * Reuses objects instead of creating new ones on each request
 * 
 * This reduces memory allocation overhead and improves performance
 * when building layouts from scratch (cache miss scenario).
 */
class Lay_Object_Pool {
    private static $row_pool = array();
    private static $col_pool = array();
    private static $el_pool = array();
    private static $pool_stats = array('created' => 0, 'reused' => 0);
    
    /**
     * Get a Row object from pool or create new one
     */
    public static function getRow($row, $index) {
        if (!empty(self::$row_pool)) {
            $obj = array_pop(self::$row_pool);
            // Re-initialize the pooled object with new data
            $obj->__construct($row, $index);
            self::$pool_stats['reused']++;
            return $obj;
        }
        self::$pool_stats['created']++;
        return new LayRow($row, $index);
    }
    
    /**
     * Get a Col object from pool or create new one
     */
    public static function getCol($col, $inside100vhRow = false, $options = array()) {
        if (!empty(self::$col_pool)) {
            $obj = array_pop(self::$col_pool);
            // Re-initialize the pooled object with new data
            $obj->__construct($col, $inside100vhRow, $options);
            self::$pool_stats['reused']++;
            return $obj;
        }
        self::$pool_stats['created']++;
        return new LayCol($col, $inside100vhRow, $options);
    }
    
    /**
     * Get an El object from pool or create new one
     */
    public static function getEl($el_arr, $is_inside_stack = false, $postid = -1, $device = 'desktop', $video_count = 0) {
        if (!empty(self::$el_pool)) {
            $obj = array_pop(self::$el_pool);
            // Re-initialize the pooled object with new data
            $obj->__construct($el_arr, $is_inside_stack, $postid, $device, $video_count);
            self::$pool_stats['reused']++;
            return $obj;
        }
        self::$pool_stats['created']++;
        return new LayEl($el_arr, $is_inside_stack, $postid, $device, $video_count);
    }
    
    /**
     * Return objects to pool for reuse
     */
    public static function releaseRow($obj) {
        if (count(self::$row_pool) < 50) { // Limit pool size to prevent memory issues
            self::$row_pool[] = $obj;
        }
    }
    
    public static function releaseCol($obj) {
        if (count(self::$col_pool) < 100) {
            self::$col_pool[] = $obj;
        }
    }
    
    public static function releaseEl($obj) {
        if (count(self::$el_pool) < 200) {
            self::$el_pool[] = $obj;
        }
    }
    
    /**
     * Clear pool (called after page generation)
     */
    public static function clearPool() {
        self::$row_pool = array();
        self::$col_pool = array();
        self::$el_pool = array();
    }
    
    /**
     * Get pool statistics
     */
    public static function getStats() {
        return self::$pool_stats;
    }
    
    /**
     * Reset statistics
     */
    public static function resetStats() {
        self::$pool_stats = array('created' => 0, 'reused' => 0);
    }
}

