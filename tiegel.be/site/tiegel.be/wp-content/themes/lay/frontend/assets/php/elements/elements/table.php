<?php

class LayTable{

    public $el;
    public $device;

    public function __construct($el, $device){
        $this->el = $el;
        $this->device = $device;
    }

    public function getMarkup($options = array()){
        $markup = '';
        
        if (!isset($this->el['config']) || !isset($this->el['config']['tableConfig'])) {
            return $markup;
        }
        
        $tableConfig = $this->el['config']['tableConfig'];
        $columnWidths = isset($this->el['config']['columnWidths']) ? $this->el['config']['columnWidths'] : array();
        $tableContent = isset($this->el['config']['cont']) ? $this->el['config']['cont'] : array();
        $relid = $this->el['relid'];
        
        // Extract table configuration
        $bordered = isset($tableConfig['bordered']) ? $tableConfig['bordered'] : false;
        $borderColor = isset($tableConfig['borderColor']) ? $tableConfig['borderColor'] : '#dee2e6';
        $borderWidth = isset($tableConfig['borderWidth']) ? $tableConfig['borderWidth'] : 1;
        $minHeight = isset($tableConfig['minHeight']) ? $tableConfig['minHeight'] : 40;
        $showVerticalBorders = isset($tableConfig['showVerticalBorders']) ? $tableConfig['showVerticalBorders'] : true;
        $showHorizontalBorders = isset($tableConfig['showHorizontalBorders']) ? $tableConfig['showHorizontalBorders'] : true;
        $showFirstLastVerticalBorders = isset($tableConfig['showFirstLastVerticalBorders']) ? $tableConfig['showFirstLastVerticalBorders'] : true;
        $showFirstLastHorizontalBorders = isset($tableConfig['showFirstLastHorizontalBorders']) ? $tableConfig['showFirstLastHorizontalBorders'] : true;
        $cellPaddingLeftRight = isset($tableConfig['cellPaddingLeftRight']) ? $tableConfig['cellPaddingLeftRight'] : 5;
        $cellPaddingTopBottom = isset($tableConfig['cellPaddingTopBottom']) ? $tableConfig['cellPaddingTopBottom'] : 5;
        $forceTextAlignment = isset($tableConfig['forceTextAlignment']) ? $tableConfig['forceTextAlignment'] : false;
		$textAlignment = isset($tableConfig['textAlignment']) ? $tableConfig['textAlignment'] : 'left';
		$verticalTextAlignment = isset($tableConfig['verticalTextAlignment']) ? $tableConfig['verticalTextAlignment'] : 'center';
		$forceTextColor = isset($tableConfig['forceTextColor']) ? $tableConfig['forceTextColor'] : false;
		$textColor = isset($tableConfig['textColor']) ? $tableConfig['textColor'] : '#000000';
		$forceTextFormat = isset($tableConfig['forceTextFormat']) ? $tableConfig['forceTextFormat'] : false;
		$textformat = isset($tableConfig['textformat']) ? $tableConfig['textformat'] : 'Default';
		$useBackgroundColor = isset($tableConfig['useBackgroundColor']) ? $tableConfig['useBackgroundColor'] : false;
		$backgroundColor = isset($tableConfig['backgroundColor']) ? $tableConfig['backgroundColor'] : '#ffffff';
        
        // Generate table classes based on config
        $hasAnyBorders = $bordered && ($showVerticalBorders || $showHorizontalBorders);
        $tableClasses = 'lay-table ' . ($hasAnyBorders ? 'lay-table-bordered' : 'lay-table-not-bordered');
        
        // Generate column widths CSS
        $columnWidthsCSS = '';
        if (!empty($columnWidths)) {
            $widths = array();
            foreach ($columnWidths as $width) {
                $value = isset($width['value']) ? $width['value'] : 'auto';
                $unit = isset($width['unit']) ? $width['unit'] : '%';
                $widths[] = ($value === 'auto' ? '1fr' : $value . $unit);
            }
            $columnWidthsCSS = implode(' ', $widths);
        } else {
            // Default to equal columns if no widths specified
            $colCount = isset($tableConfig['colNumber']) ? $tableConfig['colNumber'] : 2;
            $columnWidthsCSS = str_repeat('1fr ', $colCount);
        }
        
        $markup .= '<div class="lay-table-wrapper">';
        $markup .= '<div class="' . $tableClasses . '">';

        $selector = '#grid';
        if( $this->device == 'phone' ) {
            $selector = '#custom-phone-grid';
        }
        
        // Generate dynamic CSS styles
        $markup .= '<style>';
        $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-row{';
        $markup .= 'grid-template-columns: ' . $columnWidthsCSS . ';';
        $markup .= '}';
        
        $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-cell-content:not(.lay-table-cell-has-content) {';
        $markup .= 'min-height: ' . $minHeight . 'px;';
        $markup .= '}';
        
        $markup .= $selector .' .type-table.id-' . $relid . ' .lay-table-cell-content {';
        $markup .= 'padding: ' . $cellPaddingTopBottom . 'px ' . $cellPaddingLeftRight . 'px;';
        $markup .= '}';
        
        if ($forceTextAlignment) {
			$markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-cell-inner, ' . $selector . ' .type-table.id-' . $relid . ' .lay-table-cell-inner > * {';
			$markup .= 'text-align: ' . $textAlignment . '!important;';
			$markup .= '}';
		}
		
		if ($forceTextColor) {
			$markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-cell-inner, ' . $selector . ' .type-table.id-' . $relid . ' .lay-table-cell-inner > * {';
			$markup .= 'color: ' . $textColor . '!important;';
			$markup .= '}';
		}
		
		// Vertical text alignment is always applied
		$verticalAlignValue = 'center'; // default
		if ($verticalTextAlignment === 'top') {
			$verticalAlignValue = 'flex-start';
		} elseif ($verticalTextAlignment === 'bottom') {
			$verticalAlignValue = 'flex-end';
		}
		$markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-cell {';
		$markup .= 'align-items: ' . $verticalAlignValue . '!important;';
		$markup .= '}';
        
        if ($useBackgroundColor) {
            $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table {';
            $markup .= 'background-color: ' . $backgroundColor . ';';
            $markup .= '}';
        }
        
        if ($hasAnyBorders) {            
            if ($showHorizontalBorders && $showFirstLastHorizontalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-row:first-child .lay-table-cell {';
                $markup .= 'border-top: ' . $borderWidth . 'px solid ' . $borderColor . ';';
                $markup .= '}';
            }
            
            if ($showHorizontalBorders && $showFirstLastHorizontalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-row:last-child .lay-table-cell {';
                $markup .= 'border-bottom: ' . $borderWidth . 'px solid ' . $borderColor . ';';
                $markup .= '}';
            }
            
            if ($showVerticalBorders && $showFirstLastVerticalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-cell:first-child {';
                $markup .= 'border-left: ' . $borderWidth . 'px solid ' . $borderColor . ';';
                $markup .= '}';
            }
            
            if ($showVerticalBorders && $showFirstLastVerticalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-cell:last-child {';
                $markup .= 'border-right: ' . $borderWidth . 'px solid ' . $borderColor . ';';
                $markup .= '}';
            }
            
            if ($showVerticalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-cell {';
                $markup .= 'border-right: ' . $borderWidth . 'px solid ' . $borderColor . ';';
                $markup .= '}';
            }
            
            if ($showVerticalBorders && !$showFirstLastVerticalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-cell:last-child {';
                $markup .= 'border-right: none;';
                $markup .= '}';
            }
            
            if ($showHorizontalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-cell {';
                $markup .= 'border-bottom: ' . $borderWidth . 'px solid ' . $borderColor . ';';
                $markup .= '}';
            }
            
            if ($showHorizontalBorders && !$showFirstLastHorizontalBorders) {
                $markup .= $selector . ' .type-table.id-' . $relid . ' .lay-table-bordered .lay-table-row:last-child .lay-table-cell {';
                $markup .= 'border-bottom: none;';
                $markup .= '}';
            }
        }
        
        $markup .= '</style>';
        
        $markup .= '<div class="lay-table-body">';
        
        // Generate table rows and cells
        if (!empty($tableContent)) {
            foreach ($tableContent as $rowIndex => $row) {
                $markup .= '<div class="lay-table-row">';
                
                if (!empty($row)) {
                    foreach ($row as $cellIndex => $cell) {
                        $cellType = isset($cell['type']) ? $cell['type'] : 'text';
                        $cellContent = isset($cell['cont']) ? $cell['cont'] : '';
                        
                        $cellClasses = 'lay-table-cell';
                        $contentClasses = 'lay-table-cell-content';
                        
                        if (!empty($cellContent)) {
                            $contentClasses .= ' lay-table-cell-has-content';
                        }

                        $textformatClasses = '';
                        if ($forceTextFormat) {
                            $textformatClasses .= ' _' . $textformat . '_no_spaces using-textformat';
                        } else {
                            $textformatClasses .= ' lay-textformat-parent';
                        }

                        
                        $markup .= '<div class="' . $cellClasses . '">';
                        $markup .= '<div class="' . $contentClasses . '">';
                        $markup .= '<div class="lay-table-cell-inner ' . $textformatClasses . '">'. $cellContent.'</div>';
                        $markup .= '</div>';
                        $markup .= '</div>';
                    }
                }
                
                $markup .= '</div>';
            }
        }
        
        $markup .= '</div>'; // .lay-table-body
        $markup .= '</div>'; // .lay-table
        $markup .= '</div>'; // .lay-table-wrapper
        
        return $markup;
    }

}