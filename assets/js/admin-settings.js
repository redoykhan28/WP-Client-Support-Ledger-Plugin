jQuery(document).ready(function($) {
    // Initialize WordPress color picker
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.wcsl-color-picker-field').wpColorPicker();
    } else {
        console.warn('WCSL: WordPress Color Picker script not loaded or $.fn.wpColorPicker is not a function.');
    }
});