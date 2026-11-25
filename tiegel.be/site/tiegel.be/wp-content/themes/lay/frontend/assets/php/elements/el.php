<?php

require get_template_directory().'/frontend/assets/php/elements/elements/carousel.php';
require get_template_directory().'/frontend/assets/php/elements/elements/elementgrid.php';
require get_template_directory().'/frontend/assets/php/elements/elements/embed.php';
require get_template_directory().'/frontend/assets/php/elements/elements/hr.php';
require get_template_directory().'/frontend/assets/php/elements/elements/html.php';
require get_template_directory().'/frontend/assets/php/elements/elements/html5video.php';
require get_template_directory().'/frontend/assets/php/elements/elements/img.php';
require get_template_directory().'/frontend/assets/php/elements/elements/shortcode.php';
require get_template_directory().'/frontend/assets/php/elements/elements/stack.php';
require get_template_directory().'/frontend/assets/php/elements/elements/text.php';
require get_template_directory().'/frontend/assets/php/elements/elements/thumbnail.php';
require get_template_directory().'/frontend/assets/php/elements/elements/thumbnailgrid.php';
require get_template_directory().'/frontend/assets/php/elements/elements/video.php';
require get_template_directory().'/frontend/assets/php/elements/elements/productsgrid.php';
require get_template_directory().'/frontend/assets/php/elements/elements/textfooter.php';
require get_template_directory().'/frontend/assets/php/elements/elements/projectindex.php';
require get_template_directory().'/frontend/assets/php/elements/elements/productindex.php';
require get_template_directory().'/frontend/assets/php/elements/elements/socialmediaicons.php';
require get_template_directory().'/frontend/assets/php/elements/elements/newselement.php';
require get_template_directory().'/frontend/assets/php/elements/elements/marquee.php';
require get_template_directory().'/frontend/assets/php/elements/elements/vl.php';
require get_template_directory().'/frontend/assets/php/elements/elements/addtocartbutton.php';
require get_template_directory().'/frontend/assets/php/elements/elements/table.php';
require get_template_directory().'/frontend/assets/php/elements/elements/accordion.php';

class LayEl{

    public $type;
    public $markup = '';
    public $el_arr;
    public $device = '';
    /** @var object|null Single element instance (img, text, etc.) when not a stack */
    public $element = null;
    /** @var string */
    public $stack_opening_anchor = '';
    /** @var string */
    public $stack_ending_anchor = '';
    /** @var int|float */
    public $stack_row_gutter = 0;
    /** @var string */
    public $stack_row_gutter_mu = 'px';
    /** @var array */
    public $stack_elements_in_stack = array();
    /** @var array */
    public $stack_elements_by_row = array();
    /** @var bool */
    public $stack_is_old_format = true;

    public function __construct($el_arr, $is_inside_stack = false, $postid = -1, $device = 'desktop', $video_count = 0){
        $this->el_arr = $el_arr;
        $this->device = $device;
        // TYPE
        $this->type = $el_arr['type'];
        // for backwards compatibility
        $isProjectLink = array_key_exists( 'postid', $el_arr ) ? true : false;
        if(($this->type == 'img' && $isProjectLink) || $this->type == 'postThumbnail'){
            $this->type = 'thumbnail';
        }


        $element = false;
        $elements_in_stack = array();


        switch($this->type){
            case "thumbnail":
                $element = new LayThumbnail($el_arr);
						break;
            case "text":
                $element = new LayText($el_arr, $device, $postid);
						break;
            case "embed":
                $element = new LayEmbed($el_arr);
						break;
            case "html":
                $element = new LayHTML($el_arr);
						break;
            case "shortcode":
                $element = new LayShortcode($el_arr);
						break;
            case "hr":
                $element = new LayHr($el_arr);
						break;
            case "vl":
                $element = new LayVl($el_arr);
						break;
            case "thumbnailgrid":
                $element = new LayThumbnailGrid($el_arr, $postid);
						break;
            case "elementgrid":
                $element = new LayElementGrid($el_arr, $device);
						break;
            case "carousel":
                $element = new LayCarousel($el_arr);
						break;
            case "img":
                $element = new LayImg($el_arr);
						break;
            case "html5video":
                $element = new LayHTML5Video($el_arr);
						break;
            case "video":
                $element = new LayVideo($el_arr, false, $video_count);
						break;
            case "stack":
                $stack_el_arr = $el_arr['cont'];
                foreach( $stack_el_arr as $el ) {
                    $elements_in_stack []= Lay_Object_Pool::getEl($el, true, $postid, $device);
                }
            break;
            case "productsgrid":
                $element = new LayProductsgrid($el_arr);
            break;
            case "addtocartbutton":
                $element = new LayAddToCartButton($el_arr);
            break;
            case "textfooter":
                $element = new LayTextFooter($el_arr);
            break;
            case "projectindex":
                $element = new LayProjectIndex($el_arr);
            break;
            case "productindex":
                $element = new LayProductIndex($el_arr);
            break;
            case "socialmediaicons":
                $element = new LaySocialMediaIcons($el_arr);
            break;
            case 'news':
                $element = new LayNewsElement($el_arr);
            break;
            case 'marquee':
                $element = new LayMarquee($el_arr, $device);
            break;
            case 'table':
                $element = new LayTable($el_arr, $device);
            break;
			case 'accordion':
                $element = new LayAccordion($el_arr, $device);
            break;
            default:
                // cmb2 element (custom elements like elastique)
                if( has_filter('lay_frontend_'.$this->type) ){
                    $this->markup = apply_filters('lay_frontend_'.$this->type, $el_arr);
                }
            break;
        }
        
        if( count($elements_in_stack) > 0 ) {
            // Optional link for entire stack (reuse image link logic)
            $openingAnchor = '';
            $endingAnchor = '';
            if (array_key_exists('imagelink', $this->el_arr) && $this->el_arr['imagelink'] != false) {
                $imageLink = $this->el_arr['imagelink'];
                if (is_array($imageLink)) {
                    $linkUrl = LTUtility::getFullURL($imageLink['url']);
                    if ($imageLink['type'] == '') {
                        $linkUrl = $imageLink['url'];
                    }
                    if ($imageLink['type'] != 'category' && $imageLink['type'] != '' && $imageLink['id'] != 'null' && $imageLink['id'] != '') {
                        $linkUrl = get_permalink($imageLink['id']);
                    }
                    $openingAnchor = '<a href="'.$linkUrl.'"';
                    if (array_key_exists('newtab', $imageLink) && $imageLink['newtab'] == true) {
                        $openingAnchor .= ' target="_blank"';
                    } else {
                        if ($imageLink['id'] != '') {
                            $openingAnchor .= ' data-id="'.$imageLink['id'].'"';
                        }
                        if ($imageLink['type'] != '') {
                            $openingAnchor .= ' data-type="'.$imageLink['type'].'"';
                        }
                        if (array_key_exists('title', $imageLink) && $imageLink['title'] != '') {
                            $openingAnchor .= ' data-title="'.__($imageLink['title']).'"';
                        }
                        if (array_key_exists('catid', $imageLink) && $imageLink['catid'] != '') {
                            $openingAnchor .= ' data-catid="'.LayElFunctions::stringifyCatIds($imageLink['catid']).'"';
                        }
                    }
                } else {
                    // backwards compatibility
                    $openingAnchor = '<a href="'.LTUtility::getFullURL($imageLink).'"';
                }
                $openingAnchor .= '>';
                $endingAnchor = '</a>';
            }
            // Group elements by row for new stack format, or use old format if all elements have row = -1
            $elements_by_row = array();
            $is_old_format = true;
            
            foreach( $elements_in_stack as $el ) {
                $row = isset($el->el_arr['row']) ? $el->el_arr['row'] : -1;
                
                // Check if this is new format (elements have row >= 0)
                if ($row >= 0) {
                    $is_old_format = false;
                }
                
                if (!isset($elements_by_row[$row])) {
                    $elements_by_row[$row] = array();
                }
                $elements_by_row[$row][] = $el;
            }

            // Store stack state so getMarkup($options) can build markup with options passed to children
            $stackRowGutter = isset($this->el_arr['stackRowGutter']) ? $this->el_arr['stackRowGutter'] : 0;
            $stackRowGutterMu = isset($this->el_arr['stackRowGutterMu']) ? $this->el_arr['stackRowGutterMu'] : 'px';
            if ($stackRowGutterMu == '%') {
                $stackRowGutterMu = 'vw';
            }
            $this->stack_opening_anchor = $openingAnchor;
            $this->stack_ending_anchor = $endingAnchor;
            $this->stack_row_gutter = $stackRowGutter;
            $this->stack_row_gutter_mu = $stackRowGutterMu;
            $this->stack_elements_in_stack = $elements_in_stack;
            $this->stack_elements_by_row = $elements_by_row;
            $this->stack_is_old_format = $is_old_format;
        }
        else if( $element ) {
            // Defer markup build to getMarkup($options) so options (e.g. grid_container_selector) reach img/getMarkup
            $this->element = $element;
        }

    }

    public function getMarkup($options = array()){
        // Single (non-stack) element: render and apply the central
        // scroll-animation wrap. wrapWithScrollAnimation is a no-op
        // when scrollAnimation isn't enabled or the element type isn't
        // in the allowlist, so this is safe to apply universally.
        if (isset($this->element)) {
            $markup = $this->element->getMarkup($options);
            return LayElFunctions::wrapWithScrollAnimation($markup, $this->el_arr);
        }
        if (isset($this->stack_elements_in_stack) && count($this->stack_elements_in_stack) > 0) {
            $use_phone_stack_gutter_stylesheet = !empty($options['no_custom_phone_layout'])
                && !empty($options['grid_container_selector'])
                && isset($this->el_arr['relid']);
            $stack_wrap_css = 'width: 100%; display: flex; flex-direction: column;';
            $stack_wrap_style_tag = '';

            if ( $use_phone_stack_gutter_stylesheet ) {
                $breakpoint = isset(LayFrontend_Options::$breakpoint) ? (int) LayFrontend_Options::$breakpoint : (int) get_option('lay_breakpoint', 600);
                $selector = Lay_LayoutFunctions::grid_container_scoped_selectors( $options['grid_container_selector'], ' .col.id-' . (int) $this->el_arr['relid'] . ' .stack-wrap' );
                $desktop_css = 'gap:' . $this->stack_row_gutter . $this->stack_row_gutter_mu . ';';
                $stack_wrap_style_tag = '<style>@media (min-width: '.($breakpoint + 1).'px) { ' . $selector . ' { ' . $desktop_css . ' } }</style>';
            } else {
                $stack_wrap_css .= ' gap: ' . $this->stack_row_gutter . $this->stack_row_gutter_mu . ';';
            }

            $markup = $this->stack_opening_anchor . $stack_wrap_style_tag . '<div class="stack-wrap" style="' . $stack_wrap_css . '">';
            if ($this->stack_is_old_format) {
                foreach ($this->stack_elements_in_stack as $el) {
                    // Pass $options so LayCol can generate responsive phone media queries
                    // for paddingPhone/marginPhone (otherwise we fall back to desktop inline spacing).
                    // Note: $el->getMarkup() is recursive into LayEl::getMarkup, so
                    // any stack-inner element with scrollAnimation set gets wrapped
                    // by wrapWithScrollAnimation independently of the outer stack.
                    $col = Lay_Object_Pool::getCol($el->el_arr, false, $options);
                    $markup .= '<div class="stack-element" style="width: 100%;">' . $col->getOpeningMarkup() . $el->getMarkup($options) . $col->getClosingMarkup() . '</div>';
                }
            } else {
                foreach ($this->stack_elements_by_row as $row => $elements_in_row) {
                    $markup .= '<div class="stack-element" style="width: 100%;">';
                    foreach ($elements_in_row as $el) {
                        // Same as above: required for responsive phone padding/margin.
                        $col = Lay_Object_Pool::getCol($el->el_arr, false, $options);
                        $markup .= $col->getOpeningMarkup() . $el->getMarkup($options) . $col->getClosingMarkup();
                    }
                    $markup .= '</div>';
                }
            }
            $markup .= '</div>' . $this->stack_ending_anchor;
            // Wrap the whole stack so STACK-level scroll animation works
            // (animates all children together as one unit). Stack inner
            // elements are wrapped separately above when their own
            // scrollAnimation is enabled.
            return LayElFunctions::wrapWithScrollAnimation($markup, $this->el_arr);
        }
        return $this->markup;
    }

}