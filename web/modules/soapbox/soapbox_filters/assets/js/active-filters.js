/**
 * Active filters tasks.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.soapboxActiveFilters = {
    attach: function (context, drupalSettings) {
      if (!drupalSettings.autoSubmit) {
        const $form         = $("form[id*='views-exposed-form']");
        const $submitButton = $form.find('.js-form-submit:not([data-drupal-selector="edit-reset"])');
        let BoxId, $element;

        $('.js-active-filter', context).once().on('click', function (e) {
          BoxId = $(this).attr('for');
          $element = $("[id*='" + BoxId + "']");

          if ($element.is(':checkbox,:radio')) {
            $element.prop('checked', false);
          }
          else if ($element.is(':text')) {
            let val = $element.val();
            val = val.replace($(this).text(), "");
            val = val.replace(/(^\s*,\s*)|(\s*,\s*$)/, "");

            // For autocomplete, we strip out the (NUMBER) that Drupal adds
            // to the label. We need to strip it out here again from the value.
            if (val.startsWith('(') && val.endsWith(')')) {
              val = val.replace('(', '');
              val = val.replace(')', '');
              if (val == parseInt(val, 10)) {
                val = "";
              }
            }
            $element.val(val);
          }

          // Remove active filter label
          $(this).remove();

          // Uncheck the filter checkbox
          // Submit the exposed form
          // $form.submit();
          $submitButton.trigger('click');
        });
      }
    }
  };
})(jQuery, Drupal);
