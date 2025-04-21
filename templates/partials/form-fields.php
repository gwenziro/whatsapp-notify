<?php
/**
 * Reusable Form Fields Partial
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render text field
 * 
 * @param string $id Field ID
 * @param string $name Field name
 * @param string $label Field label
 * @param string $value Field value
 * @param string $placeholder Field placeholder
 * @param string $help_text Field help text
 * @param bool $required Is field required
 */
function wanotify_render_text_field($id, $name, $label, $value = '', $placeholder = '', $help_text = '', $required = false) {
?>
    <div class="wanotify-form-row">
        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
        <div class="wanotify-form-input">
            <input type="text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" 
                class="wanotify-input" value="<?php echo esc_attr($value); ?>" 
                placeholder="<?php echo esc_attr($placeholder); ?>" <?php echo $required ? 'required' : ''; ?>>
            <?php if (!empty($help_text)): ?>
                <p class="wanotify-help-text"><?php echo $help_text; ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php
}

/**
 * Render textarea field
 * 
 * @param string $id Field ID
 * @param string $name Field name
 * @param string $label Field label
 * @param string $value Field value
 * @param string $help_text Field help text
 * @param int $rows Number of rows
 */
function wanotify_render_textarea_field($id, $name, $label, $value = '', $help_text = '', $rows = 5) {
?>
    <div class="wanotify-form-row">
        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
        <div class="wanotify-form-input">
            <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" 
                class="wanotify-textarea" rows="<?php echo intval($rows); ?>"><?php echo esc_textarea($value); ?></textarea>
            <?php if (!empty($help_text)): ?>
                <p class="wanotify-help-text"><?php echo $help_text; ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php
}

/**
 * Render switch field
 * 
 * @param string $id Field ID
 * @param string $name Field name
 * @param string $label Field label
 * @param bool $is_checked Is switch checked
 * @param string $help_text Field help text
 */
function wanotify_render_switch_field($id, $name, $label, $is_checked = false, $help_text = '') {
?>
    <div class="wanotify-form-row">
        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
        <div class="wanotify-form-input">
            <label class="wanotify-switch">
                <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" <?php checked($is_checked); ?>>
                <span class="wanotify-slider"></span>
            </label>
            <?php if (!empty($help_text)): ?>
                <p class="wanotify-help-text"><?php echo $help_text; ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php
}
