<?php 
Frontend::get_footer();
require_once(get_template_directory().'/frontend/assets/php/dom_cursor_markup.php');
lay_render_dom_cursor_markup();

echo '<script>
(function () {
	if (!window || !window.laytheme || typeof window.laytheme.emit !== "function") {
		return;
	}
	window.laytheme.emit("newpageshownfast");
})();
</script>';

wp_footer(); 

echo '<!-- Fix for flash of unstyled content on Chrome -->';
echo '<style>.sitetitle, .laynav, .project-arrow, .mobile-title{visibility:visible;}</style>';
?>
</body>
</html>