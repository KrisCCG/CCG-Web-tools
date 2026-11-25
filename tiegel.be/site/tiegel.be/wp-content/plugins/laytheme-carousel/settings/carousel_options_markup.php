<div class="wrap" style="margin: 0;">

    <?php if(isset( $_GET['settings-updated'])) { ?>
	    <div class="updated notice inline">
	        <p>Settings updated successfully</p>
	    </div>
    <?php } ?>

	<form method="POST" action="options.php" novalidate>
		<div class="top-layoptions-submit-button-wrap">
			<h1>Carousel Addon</h1><p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
		</div>
		<div class="layoptions-quickjump-wrap">
			<div class="layoptions-quickjump">
				<a href="#lc-general">General</a>
				<a href="#lc-mousecursor">Mouse Cursor</a>
				<a href="#lc-circles">Circles as Navigation</a>
				<a href="#lc-captions">Captions for Slides</a>
				<a href="#lc-numbers">Numbers</a>
				<a href="#lc-buttons">Previous/Next Buttons</a>
				<a href="#lc-text-spacings">Text Spacings</a>
				<a href="#lc-other">Other</a>
			</div>
		</div>
		<div class="lay-explanation <?php echo get_option('expand-how_to_use_carousel', 'expanded'); ?>" data-expand-status-option-name="expand-how_to_use_carousel">
			<header>
				<h3 class="title">How to use</h3>
				<button class="lay-explanation-handle"></button>
			</header>
			<div class="lay-explanation-inner">
				<p>Add a carousel in the Gridder by clicking "+More" &rarr; "+Carousel".</p>
				<p>For "Transition Type" of "Horizontal Scroll", some of the settings don't apply. Like "Loop", "Autoplay" and "Align Captions and Numbers to width of slides".</p>
				<p>Want to use a SVG as a mouse cursor or previous/next button? <a href="http://laythemeforum.com/topic/5190/svg-cursors-fix" target="_blank">Read this.</a></p>
			</div>
		</div>
		<?php settings_fields( 'manage-laycarousel' );	//pass slug name of page, also referred
		                                        //to in Settings API as option group name
		do_settings_sections( 'manage-laycarousel' ); 	//pass slug name of page
		submit_button('Save Changes');
		?>
	</form>
</div>
