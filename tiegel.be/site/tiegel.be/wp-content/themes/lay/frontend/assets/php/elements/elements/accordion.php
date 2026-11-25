<?php

class LayAccordion{

    public $el;
    public $device;

    public function __construct($el, $device){
        $this->el = $el;
        $this->device = $device;
    }

    public function getMarkup($options = array()){
        $markup = '';
        
        if (!isset($this->el['config']) || !isset($this->el['config']['accordionConfig'])) {
            return $markup;
        }
        
        $accordionConfig = $this->el['config']['accordionConfig'];
        $accordionItems = isset($this->el['config']['cont']) ? $this->el['config']['cont'] : array();
        $relid = $this->el['relid'];
        
        // Extract accordion configuration
        $showBorders = isset($accordionConfig['showBorders']) ? $accordionConfig['showBorders'] : true;
        $borderColor = isset($accordionConfig['borderColor']) ? $accordionConfig['borderColor'] : '#dee2e6';
        $borderWidth = isset($accordionConfig['borderWidth']) ? $accordionConfig['borderWidth'] : 1;
        $showFirstLastBorders = isset($accordionConfig['showFirstLastBorders']) ? $accordionConfig['showFirstLastBorders'] : false;
        $showBorderUnderOpenedTitle = isset($accordionConfig['showBorderUnderOpenedTitle']) ? $accordionConfig['showBorderUnderOpenedTitle'] : false;
        $animationDuration = isset($accordionConfig['animationDuration']) ? $accordionConfig['animationDuration'] : 0;
        $closeOthers = isset($accordionConfig['closeOthers']) ? $accordionConfig['closeOthers'] : true;
        $collapseOnContentClick = isset($accordionConfig['collapseOnContentClick']) ? $accordionConfig['collapseOnContentClick'] : false;
        $iconType = isset($accordionConfig['iconType']) ? $accordionConfig['iconType'] : 'arrows';
        $iconPosition = isset($accordionConfig['iconPosition']) ? $accordionConfig['iconPosition'] : 'left';
        $titlePaddingTop = isset($accordionConfig['titlePaddingTop']) ? $accordionConfig['titlePaddingTop'] : 5;
        $titlePaddingRight = isset($accordionConfig['titlePaddingRight']) ? $accordionConfig['titlePaddingRight'] : 5;
        $titlePaddingBottom = isset($accordionConfig['titlePaddingBottom']) ? $accordionConfig['titlePaddingBottom'] : 5;
        $titlePaddingLeft = isset($accordionConfig['titlePaddingLeft']) ? $accordionConfig['titlePaddingLeft'] : 0;
        $contentPaddingTop = isset($accordionConfig['contentPaddingTop']) ? $accordionConfig['contentPaddingTop'] : 10;
        $contentPaddingRight = isset($accordionConfig['contentPaddingRight']) ? $accordionConfig['contentPaddingRight'] : 0;
        $contentPaddingBottom = isset($accordionConfig['contentPaddingBottom']) ? $accordionConfig['contentPaddingBottom'] : 10;
        $contentPaddingLeft = isset($accordionConfig['contentPaddingLeft']) ? $accordionConfig['contentPaddingLeft'] : 0;
        $iconSpacing = isset($accordionConfig['iconSpacing']) ? $accordionConfig['iconSpacing'] : 10;
        $iconYOffset = isset($accordionConfig['iconYOffset']) ? $accordionConfig['iconYOffset'] : 0;
        $iconSize = isset($accordionConfig['iconSize']) ? $accordionConfig['iconSize'] : 25;
        $iconStrokeWidth = isset($accordionConfig['iconStrokeWidth']) ? $accordionConfig['iconStrokeWidth'] : 1;
        $iconColor = isset($accordionConfig['iconColor']) ? $accordionConfig['iconColor'] : '#bbbbbb';
        $maxWidth = isset($accordionConfig['maxWidth']) ? $accordionConfig['maxWidth'] : 90;
        $useBackgroundColor = isset($accordionConfig['useBackgroundColor']) ? $accordionConfig['useBackgroundColor'] : false;
        $backgroundColor = isset($accordionConfig['backgroundColor']) ? $accordionConfig['backgroundColor'] : '#ffffff';
        $forceTitleTextFormat = isset($accordionConfig['forceTitleTextFormat']) ? $accordionConfig['forceTitleTextFormat'] : false;
        $titleTextformat = isset($accordionConfig['titleTextformat']) ? $accordionConfig['titleTextformat'] : 'Default';
        $forceTitleTextColor = isset($accordionConfig['forceTitleTextColor']) ? $accordionConfig['forceTitleTextColor'] : false;
        $titleTextColor = isset($accordionConfig['titleTextColor']) ? $accordionConfig['titleTextColor'] : '#000000';
        $forceContentTextFormat = isset($accordionConfig['forceContentTextFormat']) ? $accordionConfig['forceContentTextFormat'] : false;
        $contentTextformat = isset($accordionConfig['contentTextformat']) ? $accordionConfig['contentTextformat'] : 'Default';
        $forceContentTextColor = isset($accordionConfig['forceContentTextColor']) ? $accordionConfig['forceContentTextColor'] : false;
        $contentTextColor = isset($accordionConfig['contentTextColor']) ? $accordionConfig['contentTextColor'] : '#000000';
        $rowAmount = isset($accordionConfig['rowAmount']) ? $accordionConfig['rowAmount'] : 3;
        $preOpenFirstRow = isset($accordionConfig['preOpenFirstRow']) ? $accordionConfig['preOpenFirstRow'] : false;
        
        $selector = '#grid';
        if( $this->device == 'phone' ) {
            $selector = '#custom-phone-grid';
        }
        $grid_container_selector = array_key_exists('grid_container_selector', $options) ? $options['grid_container_selector'] : '';

        // Build a scoped CSS selector that ends with the full per-rule suffix.
        // We can't just concat the suffix onto a comma-joined base selector —
        // the suffix would only attach to the LAST root. Apply the full
        // suffix to each root via grid_container_scoped_selectors.
        $build_sel = function( $suffix ) use ( $selector, $relid, $grid_container_selector ) {
            $full_suffix = ' .id-' . (int) $relid . $suffix;
            if ( $grid_container_selector !== '' ) {
                return Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_container_selector, $full_suffix );
            }
            return $selector . $full_suffix;
        };

        // Generate dynamic CSS styles
        $markup .= '<style>';

        // Accordion item borders
        $markup .= $build_sel( ' .lay-accordion-item:not(:last-child)' ) . ' {';
        $markup .= 'border-bottom: ' . ($showBorders ? $borderWidth . 'px solid ' . $borderColor : 'none') . ';';
        $markup .= '}';
        
        if ($showBorders && $showFirstLastBorders) {
            $markup .= $build_sel( ' .lay-accordion-item:first-child' ) . ' {';
            $markup .= 'border-top: ' . $borderWidth . 'px solid ' . $borderColor . ';';
            $markup .= '}';

            $markup .= $build_sel( ' .lay-accordion-item:last-child' ) . ' {';
            $markup .= 'border-bottom: ' . $borderWidth . 'px solid ' . $borderColor . ';';
            $markup .= '}';
        }

        // Title styling
        $markup .= $build_sel( ' .lay-accordion-title' ) . ' {';
        $markup .= 'padding: ' . $titlePaddingTop . 'px ' . $titlePaddingRight . 'px ' . $titlePaddingBottom . 'px ' . $titlePaddingLeft . 'px;';
        $markup .= 'background-color: ' . ($useBackgroundColor ? $backgroundColor : 'transparent') . ';';
        $markup .= '}';

        // Content styling with grid animation
        $markup .= $build_sel( ' .lay-accordion-content' ) . ' {';
        $markup .= 'background-color: ' . ($useBackgroundColor ? $backgroundColor : 'transparent') . ';';
        $markup .= 'display: grid;';
        $markup .= 'grid-template-rows: 0fr;';
        $markup .= 'transition: grid-template-rows ' . $animationDuration . 'ms;';
        $markup .= 'overflow: hidden;';
        $markup .= '}';

        $markup .= $build_sel( ' .lay-accordion-content.closed' ) . ' {';
        $markup .= 'grid-template-rows: 0fr;';
        $markup .= '}';

        $markup .= $build_sel( ' .lay-accordion-content.open' ) . ' {';
        $markup .= 'grid-template-rows: 1fr;';
        $markup .= '}';

        // Content text styling
        $markup .= $build_sel( ' .lay-accordion-content-text' ) . '{';
        $markup .= 'border-top: ' . ($showBorders && $showBorderUnderOpenedTitle ? $borderWidth . 'px solid ' . $borderColor : 'none') . ';';
        $markup .= 'padding: ' . $contentPaddingTop . 'px ' . $contentPaddingRight . 'px ' . $contentPaddingBottom . 'px ' . $contentPaddingLeft . 'px!important;';
        $markup .= 'overflow: hidden;';
        $markup .= '}';

        // Icon styling
        $markup .= $build_sel( ' .lay-accordion-icon' ) . ' {';
        $markup .= 'margin-right: ' . ($iconPosition === 'left' ? $iconSpacing : 0) . 'px;';
        $markup .= 'margin-left: ' . ($iconPosition === 'right' ? $iconSpacing : 0) . 'px;';
        $markup .= '}';

        $markup .= $build_sel( ' .lay-accordion-icon svg' ) . ' {';
        $markup .= 'position: relative;';
        $markup .= 'top: ' . $iconYOffset . 'px;';
        $markup .= 'color: ' . $iconColor . '!important;';
        $markup .= '}';

        // Content max width
        $markup .= $build_sel( ' .lay-accordion-content-text > *' ) . ' {';
        $markup .= 'max-width: ' . $maxWidth . '%;';
        $markup .= '}';

        // Title text color
        if ($forceTitleTextColor) {
            $markup .= $build_sel( ' .lay-accordion-title-text > *' ) . ' {';
            $markup .= 'color: ' . $titleTextColor . '!important;';
            $markup .= '}';
        }

        // Content text color
        if ($forceContentTextColor) {
            $markup .= $build_sel( ' .lay-accordion-content-text > *' ) . ' {';
            $markup .= 'color: ' . $contentTextColor . '!important;';
            $markup .= '}';
        }
        
        $markup .= '</style>';
        
        $closeOthersClass = $closeOthers ? 'lay-accordion-close-others' : 'lay-accordion-allow-multiple';
        $collapseOnContentClickAttr = $collapseOnContentClick ? ' data-collapse-on-content-click="1"' : '';
        $markup .= '<div class="lay-accordion-wrapper ' . $closeOthersClass . '"' . $collapseOnContentClickAttr . '>';
        $markup .= '<div class="lay-accordion">';
        
        // Generate accordion items
        if (!empty($accordionItems)) {
            $itemsToShow = array_slice($accordionItems, 0, $rowAmount);
            foreach ($itemsToShow as $index => $item) {
                $title = isset($item['title']) ? $item['title'] : '';
                $content = isset($item['content']) ? $item['content'] : '';
                $isOpen = $preOpenFirstRow && $index === 0;
                
                $markup .= '<div class="lay-accordion-item">';
                
                // Title
                $titleClasses = 'lay-accordion-title';
                if ($iconPosition === 'right') {
                    $titleClasses .= ' icon-right';
                }
                $markup .= '<div class="' . $titleClasses . '">';
                
                if ($iconPosition === 'left') {
                    $markup .= '<div class="lay-accordion-icon">';
                    $markup .= '<div class="lay-accordion-icon-open" style="display: ' . ($isOpen ? 'block' : 'none') . ';">' . $this->getIconContent($iconType, true, $iconSize, $iconStrokeWidth) . '</div>';
                    $markup .= '<div class="lay-accordion-icon-closed" style="display: ' . ($isOpen ? 'none' : 'block') . ';">' . $this->getIconContent($iconType, false, $iconSize, $iconStrokeWidth) . '</div>';
                    $markup .= '</div>';
                }
                
                $titleTextformatClasses = '';
                if ($forceTitleTextFormat) {
                    $titleTextformatClasses .= ' _' . $titleTextformat . '_no_spaces using-textformat';
                } else {
                    $titleTextformatClasses .= ' lay-textformat-parent';
                }
                
                $markup .= '<div class="lay-accordion-title-text ' . $titleTextformatClasses . '">' . $title . '</div>';
                
                if ($iconPosition === 'right') {
                    $markup .= '<div class="lay-accordion-icon">';
                    $markup .= '<div class="lay-accordion-icon-open" style="display: ' . ($isOpen ? 'block' : 'none') . ';">' . $this->getIconContent($iconType, true, $iconSize, $iconStrokeWidth) . '</div>';
                    $markup .= '<div class="lay-accordion-icon-closed" style="display: ' . ($isOpen ? 'none' : 'block') . ';">' . $this->getIconContent($iconType, false, $iconSize, $iconStrokeWidth) . '</div>';
                    $markup .= '</div>';
                }
                
                $markup .= '</div>';
                
                // Content
                $contentClasses = 'lay-accordion-content ' . ($isOpen ? 'open' : 'closed');
                $markup .= '<div class="' . $contentClasses . '">';
                $markup .= '<div class="lay-accordion-content-inner">';
                
                $contentTextformatClasses = '';
                if ($forceContentTextFormat) {
                    $contentTextformatClasses .= ' _' . $contentTextformat . '_no_spaces using-textformat';
                } else {
                    $contentTextformatClasses .= ' lay-textformat-parent';
                }
                
                $markup .= '<div class="lay-accordion-content-text ' . $contentTextformatClasses . '">' . $content . '</div>';
                $markup .= '</div>';
                $markup .= '</div>';
                
                $markup .= '</div>'; // .lay-accordion-item
            }
        }
        
        $markup .= '</div>'; // .lay-accordion
        $markup .= '</div>'; // .lay-accordion-wrapper
        
        return $markup;
    }
    
    private function getIconContent($iconType, $isOpen, $iconSize, $iconStrokeWidth) {
        $viewBoxSize = $iconSize;
        
        switch ($iconType) {
            case 'plus-minus':
                return $isOpen ? 
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><line x1="' . ($viewBoxSize * 0.2) . '" y1="' . ($viewBoxSize * 0.5) . '" x2="' . ($viewBoxSize * 0.8) . '" y2="' . ($viewBoxSize * 0.5) . '"></line></svg>' :
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><line x1="' . ($viewBoxSize * 0.5) . '" y1="' . ($viewBoxSize * 0.2) . '" x2="' . ($viewBoxSize * 0.5) . '" y2="' . ($viewBoxSize * 0.8) . '"></line><line x1="' . ($viewBoxSize * 0.2) . '" y1="' . ($viewBoxSize * 0.5) . '" x2="' . ($viewBoxSize * 0.8) . '" y2="' . ($viewBoxSize * 0.5) . '"></line></svg>';
                    
            case 'plus-x':
                return $isOpen ? 
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><line x1="' . ($viewBoxSize * 0.75) . '" y1="' . ($viewBoxSize * 0.25) . '" x2="' . ($viewBoxSize * 0.25) . '" y2="' . ($viewBoxSize * 0.75) . '"></line><line x1="' . ($viewBoxSize * 0.25) . '" y1="' . ($viewBoxSize * 0.25) . '" x2="' . ($viewBoxSize * 0.75) . '" y2="' . ($viewBoxSize * 0.75) . '"></line></svg>' :
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><line x1="' . ($viewBoxSize * 0.5) . '" y1="' . ($viewBoxSize * 0.2) . '" x2="' . ($viewBoxSize * 0.5) . '" y2="' . ($viewBoxSize * 0.8) . '"></line><line x1="' . ($viewBoxSize * 0.2) . '" y1="' . ($viewBoxSize * 0.5) . '" x2="' . ($viewBoxSize * 0.8) . '" y2="' . ($viewBoxSize * 0.5) . '"></line></svg>';
                    
            case 'arrows':
                return $isOpen ? 
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><polyline points="' . ($viewBoxSize * 0.75) . ',' . ($viewBoxSize * 0.625) . ' ' . ($viewBoxSize * 0.5) . ',' . ($viewBoxSize * 0.375) . ' ' . ($viewBoxSize * 0.25) . ',' . ($viewBoxSize * 0.625) . '"></polyline></svg>' :
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><polyline points="' . ($viewBoxSize * 0.25) . ',' . ($viewBoxSize * 0.375) . ' ' . ($viewBoxSize * 0.5) . ',' . ($viewBoxSize * 0.625) . ' ' . ($viewBoxSize * 0.75) . ',' . ($viewBoxSize * 0.375) . '"></polyline></svg>';
                    
            default:
                return $isOpen ? 
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><line x1="' . ($viewBoxSize * 0.2) . '" y1="' . ($viewBoxSize * 0.5) . '" x2="' . ($viewBoxSize * 0.8) . '" y2="' . ($viewBoxSize * 0.5) . '"></line></svg>' :
                    '<svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" fill="none" stroke="currentColor" stroke-width="' . $iconStrokeWidth . '"><line x1="' . ($viewBoxSize * 0.5) . '" y1="' . ($viewBoxSize * 0.2) . '" x2="' . ($viewBoxSize * 0.5) . '" y2="' . ($viewBoxSize * 0.8) . '"></line><line x1="' . ($viewBoxSize * 0.2) . '" y1="' . ($viewBoxSize * 0.5) . '" x2="' . ($viewBoxSize * 0.8) . '" y2="' . ($viewBoxSize * 0.5) . '"></line></svg>';
        }
    }

}
