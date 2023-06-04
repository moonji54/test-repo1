(function ($) {

  'use strict';

  /**
   * Invalid event handler for input elements in Details field group.
   */
  var onDetailsInvalid = function(e) {
    // Open any hidden parents first.
    $(e.target).parents('details:not([open])').each(function () {
      $(this).attr('open', '');
    });
  }

  /**
   * Behaviors for details validation.
   */
  Drupal.behaviors.fieldGroupDetailsValidation = {
    attach: function (context) {
      $('.field-group-details :input', context).once('field-group-details-validation').on('invalid.field_group', onDetailsInvalid);
    }
  };

})(jQuery);
