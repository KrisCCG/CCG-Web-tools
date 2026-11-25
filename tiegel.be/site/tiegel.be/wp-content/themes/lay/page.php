<?php
get_header(); 
?>
<?php 
global $post;
echo LaySearch::getMarkup();

if ( post_password_required( $post ) ) {
    echo get_the_password_form();
} else {
    echo LayIntro::getMarkup(); 

	if ( class_exists('WooCommerce') && have_posts() ) {
			$content = get_the_content();
			if(trim($content) != '') {
				?>
				<div id="lay-woocommerce" class="lay-textformat-parent">
				<?php                
					the_content();
				?>
				</div><!-- #lay-woocommerce -->
				<?php
			}		
	}

$layout = Lay_Layout::getLayoutInit();
echo $layout;
?>
<div id="lay-overlays">
<?php
echo Lay_Overlays::getOverlays($layout);
?>
</div>
<?php
echo ProjectsOverlaysOptions::getMarkup();
echo Lay_Layout::getFixedHeader();
?>
<?php
}
get_footer();
