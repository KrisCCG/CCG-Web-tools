<div class="wrap" style="margin: 0;">
<form method="post" action="options.php" novalidate>
    <div class="top-layoptions-submit-button-wrap">
        <h1><?php echo lay_t('Webfonts', 'webfonts'); ?></h1>
    </div>
    <div class="lay-explanation add-webfont-explanation <?php echo get_option('expand-how_to_add_webfont', 'collapsed'); ?>" data-expand-status-option-name="expand-how_to_add_webfont">
        <header>
            <h3 class="title"><?php echo lay_t('How to add a Webfont', 'how_to_add_a_webfont') ?></h3>
            <button class="lay-explanation-handle"></button>
        </header>
        <div class="lay-explanation-inner">
            <h3 class="title"><?php echo lay_t('Add by font file', 'add_by_font_file') ?></h3>
            <p>
                <?php echo lay_t('You can use .ttf, .woff2 or .woff files. The .woff font-format is the most widely supported Webfont format.', 'you_can_use_ttf_woff2_or_woff_files_the_woff_font_format_is_the_most_widely_supported_webfont_format') ?>
                <br><?php echo lay_t('Make sure you are using a free webfont or have bought a webfont-license for this font!', 'make_sure_you_are_using_a_free_webfont_or_have_bought_a_webfont_license_for_this_font') ?>
            </p>
            <p>
                <?php echo lay_t('Don\'t have a .ttf, .woff2 or .woff? You can convert your font file to a supported format on', 'don_t_have_a_ttf_woff2_or_woff_you_can_convert_your_font_file_to_a_supported_format_on') ?> <a target="_blank" href="https://everythingfonts.com/">everythingfonts.com</a> &rarr; "Font Conversion".
            </p>
            <p>
                <?php echo lay_t('Click on', 'click_on') ?> <span class="lay-label label-info"><?php echo lay_t('+ Add Font', 'add_font') ?></span> <?php echo lay_t('below and then on', 'below_and_then_on') ?> <span class="lay-label label-info"><?php echo lay_t('Add by file', 'add_by_file') ?></span> <?php echo lay_t('to upload your file.', 'to_upload_your_file') ?>
            </p>

            <h3 style="padding-top: 20px;margin-top:0;"><?php echo lay_t('Or add by "&lt;link&gt;" tag and CSS', 'or_add_by_andlt_linkandgt_tag_and_css') ?></h3>
            <p>
                <?php echo lay_t('Click on', 'click_on') ?> <span class="lay-label label-info"><?php echo lay_t('+ Add Font', 'add_font') ?></span> <?php echo lay_t('below and then on', 'below_and_then_on') ?> <span class="lay-label label-info"><?php echo lay_t('Add by "&lt;link&gt;" tag and CSS', 'add_by_andlt_linkandgt_tag_and_css') ?></span>. <?php echo lay_t('Enter a font name, the "&lt;link&gt;" tag and your font-family CSS rule.', 'enter_a_font_name_the_andlt_linkandgt_tag_and_your_font_family_css_rule') ?>
            </p>

            <h3 style="padding-top: 20px;margin-top:0;"><?php echo lay_t('Or add by "&lt;script&gt;" tag and CSS', 'or_add_by_andlt_scriptandgt_tag_and_css') ?></h3>
            <p>
                <?php echo lay_t('Click on', 'click_on') ?> <span class="lay-label label-info"><?php echo lay_t('+ Add Font', 'add_font') ?></span> <?php echo lay_t('below and then on', 'below_and_then_on') ?> <span class="lay-label label-info"><?php echo lay_t('Add by "&lt;script&gt;" tag and CSS', 'add_by_andlt_scriptandgt_tag_and_css') ?></span>. <?php echo lay_t('Enter a font name, the JavaScript code and your font-family CSS rule.', 'enter_a_font_name_the_javascript_code_and_your_font_family_css_rule') ?><br>
                <?php echo lay_t('Please note that Webfonts added by "&lt;script&gt;" tag and CSS are not displayed correctly in the text editor. Also, here in the Webfonts Manager they will only be displayed correctly after you refresh the page.', 'please_note_that_webfonts_added_by_andlt_scriptandgt_tag_and_css_are_not_displayed_correctly_in_the_text_editor_also_here_in_the_webfonts_manager_they_will_only_be_displayed_correctly_after_you_refresh_the_page') ?>
                <!-- Remember to click <span class="lay-label label-info">Save Changes</span> when you are done. -->
            </p>

            <h3 style="padding-top: 20px;margin-top:0;" class="title"><?php echo lay_t('Usage', 'usage') ?></h3>
            <p>
                <?php echo lay_t('After you added your Webfont it is ready to use. It will appear in the "Font Family" dropdown menus in', 'after_you_added_your_webfont_it_is_ready_to_use_it_will_appear_in_the_font_family_dropdown_menus_in') ?> <a href="<?php echo admin_url( 'admin.php?page=manage-textformats' ); ?>"><?php echo lay_t('Text Formats', 'text_formats') ?></a><?php echo lay_t(', in the Customizer and when you edit texts in "Projects" and "Pages".', 'in_the_customizer_and_when_you_edit_texts_in_projects_and_pages') ?>
            </p>
        </div>
    </div>

    <!-- React FontManager will mount here -->
    <div id="fontmanager"></div>

    <?php settings_fields( 'manage-webfonts' ); ?>
    <?php do_settings_sections( 'manage-webfonts' ); ?>
</form>
</div>

<!-- FontManager will auto-initialize when the JS loads -->