<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">

<?php wp_head();
Frontend::get_meta();
Frontend::get_custom_head_content();
Frontend::get_max_width_option_css();
Frontend::get_custom_lay_css();

$theme = wp_get_theme();
echo '<!-- Version '.$theme->get( 'Version' ).'-->';
echo '<!-- Fix for flash of unstyled content on Chrome -->';
echo '<style>.sitetitle, .laynav, .project-arrow, .mobile-title{visibility:hidden;}</style>';
?>
</head>

<body <?php body_class(); ?> <?php echo Frontend::get_body_data(); ?>>
<?php
wp_body_open();
do_action('laytheme_after_bodytag');
$image_loading_effect = get_option('misc_options_image_loading_blur', 'standard');

// Handle backwards compatibility for old checkbox values
if ($image_loading_effect === 'on') {
    $image_loading_effect = 'blur';
} elseif ($image_loading_effect === '') {
    $image_loading_effect = 'standard';
}

// The "blur" effect now uses native CSS `filter: blur(25px) + clip-path: inset(0)`
// (see body.lazyload-blur in style.scss) — no SVG filter needed and avoids
// an iOS 26 Safari paint bug we hit with the previous `filter: url(#sharp-blur)`
// approach. The pixelated effects still need SVG filters because their
// `feFlood + feComposite + feTile + feMorphology` chain has no native CSS
// equivalent.
if($image_loading_effect == 'pixelated' || $image_loading_effect == 'pixelated-animated') {
    if(get_option('misc_options_image_loading', 'on') == 'on') {
        // width="0" height="0" + aria-hidden + focusable="false" is the canonical
        // pattern for an SVG that exists only to host filter definitions.
        // The .svg-filter-wrap CSS (positioning) is a defense-in-depth backup —
        // the SVG attributes are what reliably hide it across browsers.
        echo '<svg class="svg-filter-wrap" width="0" height="0" aria-hidden="true" focusable="false"><defs>';

        if($image_loading_effect == 'pixelated') {
            echo '<filter id="pixelate">
                    <feFlood x="4" y="4" height="2" width="2"/>
                    <feComposite width="16" height="16"/>
                    <feTile result="a"/>
                    <feComposite in="SourceGraphic" in2="a" operator="in"/>
                    <feMorphology operator="dilate" radius="8"/>
                </filter>';
        } elseif($image_loading_effect == 'pixelated-animated') {
            echo '<filter id="pixelateAni" x="0" y="0" width="100%" height="100%">
                <feFlood x="1" y="1" height="1" width="1"/>
                <feComposite id="composite" in2="SourceGraphic" operator="in" width="24" height="24"/>
                <feTile result="tiled"/>
                <feComposite in="SourceGraphic" in2="tiled" operator="in"/>
                <feMorphology id="morphology" operator="dilate" radius="12"/>
            </filter>';
        }

        echo '</defs></svg>';
    }
}


Frontend::get_header();
Frontend::get_project_arrows();