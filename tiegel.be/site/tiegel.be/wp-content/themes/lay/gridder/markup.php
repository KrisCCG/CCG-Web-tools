<div id="gridder-modals" class="bt-css">
	<div id="text-input-modal" class="lay-input-modal">
		<div class="text-modal-notices-wrap">
		<?php
			if(get_option( 'lay_show_texteditor_notice_for_linebreak', '' ) == ''){
				echo
				'<div data-optionname="lay_show_texteditor_notice_for_linebreak" class="text-modal-notice notice_for_linebreak">
					<div class="lay-texteditor-explanation-text">
						<span>
							Press "Shift" + "Enter" for a linebreak. Press only "Enter" for a new paragraph.
						</span>
					</div>
					<div class="lay-texteditor-tip-buttons">
						<button type="button" class="btn btn-default btn-xs dont-show-again">Don\'t show again</button>
						<button type="button" class="btn btn-default btn-xs next-tip">Next</button>
					</div>
				</div>';
			}
			if(get_option( 'lay_show_texteditor_notice_for_textformats', '' ) == ''){
				echo
				'<div data-optionname="lay_show_texteditor_notice_for_textformats" class="text-modal-notice">
					<div class="lay-texteditor-explanation-text">
						<span>
							Want to change the default Text Style? Or create a Text Style for Paragraphs or Headlines and use it anywhere? <a target="_blank" href="'.get_admin_url( null, "admin.php?page=manage-textformats").'">Use Textformats!</a>
						</span>
					</div>
					<div class="lay-texteditor-tip-buttons">
						<button type="button" class="btn btn-default btn-xs dont-show-again">Don\'t show again</button>
						<button type="button" class="btn btn-default btn-xs next-tip">Next</button>
					</div>
				</div>';
			}
			if(get_option( 'lay_show_texteditor_notice_for_clear_formatting', '' ) == ''){
				echo
				'<div data-optionname="lay_show_texteditor_notice_for_clear_formatting" class="text-modal-notice">
					<div class="lay-texteditor-explanation-text">
						<span>
							Did you apply a Text Format but it doesn\'t look right? Try these steps:
							<div style="margin: 10px 0;">
								- Select your text<br>
								- Click <img class="lazyload" data-src="'.get_template_directory_uri().'/gridder/assets/img/textmodal_notices/clear_formatting.png" alt=""> "Clear formatting"<br>
								- Apply your Text Format‚
							</div>
						</span>
					</div>
					<div class="lay-texteditor-tip-buttons">
						<button type="button" class="btn btn-default btn-xs dont-show-again">Don\'t show again</button>
						<button type="button" class="btn btn-default btn-xs next-tip">Next</button>
					</div>
				</div>';
			}
			if(get_option( 'lay_show_texteditor_notice_for_nonbreakingspace', '' ) == ''){
				echo
				'<div data-optionname="lay_show_texteditor_notice_for_nonbreakingspace" class="text-modal-notice">
					<div class="lay-texteditor-explanation-text">
						<span>
							Need a space but want to prevent a linebreak? Use a <img class="lazyload" data-src="'.get_template_directory_uri().'/gridder/assets/img/textmodal_notices/nonbreaking_space.png" alt=""> "Nonbreaking space".
						</span>
					</div>
					<div class="lay-texteditor-tip-buttons">
						<button type="button" class="btn btn-default btn-xs dont-show-again">Don\'t show again</button> 
						<button type="button" class="btn btn-default btn-xs next-tip">Next</button>
					</div>
				</div>';
			}
			if(get_option( 'lay_show_texteditor_notice_for_softhyphen', '' ) == ''){
				echo
				'<div data-optionname="lay_show_texteditor_notice_for_softhyphen" class="text-modal-notice">
					<div class="lay-texteditor-explanation-text">
						<span>
							Have a long word that overflows its text column? Use a <img class="lazyload" data-src="'.get_template_directory_uri().'/gridder/assets/img/textmodal_notices/soft_hyphen.png" alt=""> "Soft hyphen" to make the word break at a certain position. <a href="http://en.wikipedia.org/wiki/Soft_hyphen" target="_blank">more info</a>
						</span>
					</div>
					<div class="lay-texteditor-tip-buttons">
						<button type="button" class="btn btn-default btn-xs dont-show-again">Don\'t show again</button>
						<button type="button" class="btn btn-default btn-xs next-tip">Close</button>
					</div>
				</div>';
			}
			// attention! only last tip should have 'close' as next-button text instead of 'next tip'
		?>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title js-text-modal-title">Add Text</h3>
				<button type="button" class="close close-modal-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
			</div>
			<div class="panel-body">
				<div class="insert-qtx-lsb text-modal-language-switcher-wrap"></div>
				<?php wp_editor( "", "gridder_text_editor", Gridder::$tinymceSettings ); ?>
			</div>
			<div class="panel-footer clearfix"><button type="button" class="btn btn-default btn-lg remove-text-btn" style="display:none">Remove Text</button><button type="button" class="btn btn-default btn-lg add-text-btn">Ok</button></div>
		</div>
		<div class="background"></div>
	</div>
	<div id="gridder-header-cmb2-buttons">
		<?php do_action( 'lay_after_gridder_buttons' ); ?>
	</div>

	<?php do_action( 'lay_echo_cmb2_modals' ); ?>
</div>
<script language="javascript">
	<?php if (get_current_screen()->id == 'edit-category') { ?>
		// category
		jQuery('.form-table').first().after(jQuery('#gridder-modals'));
		jQuery('.form-table').first().after('<div id="gridder"></div>');
	<?php } else { 
		$show_gridder_above_metaboxes = get_option('misc_options_show_gridder_above_metaboxes', '');
		if($show_gridder_above_metaboxes == 'on'){
			echo 'jQuery("#postbox-container-2").prepend("<div id=\"gridder\"></div>");';
			echo 'jQuery("#postbox-container-2").prepend(jQuery("#gridder-modals"));';
		} else {
			echo 'jQuery("#postbox-container-2").append("<div id=\"gridder\"></div>");';
			echo 'jQuery("#postbox-container-2").append(jQuery("#gridder-modals"));';
		}
		?>
	<?php } ?>
	jQuery('#gridder-metabox').remove();
	jQuery('#gridder-modals > *').hide();
</script>