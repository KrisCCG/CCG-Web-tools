<div>
    <!-- React FormatsManager will mount here -->
    <div id="formatsmanager"></div>

    <!-- Hidden textareas for backwards compatibility and initialization -->
    <textarea id="formatsmanager_json" style="display: none;"><?php echo FormatsManager::getDefaultFormatsJson(); ?></textarea>
    <textarea id="fontmanager_json" style="display: none;"><?php echo get_option('fontmanager_json'); ?></textarea>
</div>

<!-- FormatsManager will auto-initialize when the JS loads -->