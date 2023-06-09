
(function (Drupal, once, $) {

  'use strict';

  Drupal.behaviors.editor_advanced_link = {
    attach: function (context, settings) {
      // Reset modal window position when advanced details element is opened or
      // closed to prevent the element content to be out of the screen.
      once('editor_advanced_link', '.editor-link-dialog details[data-drupal-selector="edit-advanced"]').forEach((details) => {
        details.addEventListener('toggle', () => {
          $("#drupal-modal").dialog({
            position: {
              of: window
            }
          });
        });
      });

      // Add noopener to rel attribute if open link in new window checkbox is
      // checked.
      if (context.querySelector('input[data-drupal-selector="edit-attributes-rel"]')) {
        once('editor_advanced_linktargetrel', 'input[data-drupal-selector="edit-attributes-target"]').forEach((element) => {
          element.addEventListener('change', (evt) => {
            var checkbox = evt.currentTarget;
            var rel_attribute_field = document.querySelector('input[data-drupal-selector="edit-attributes-rel"]');

            var rel_attributes = rel_attribute_field.value.split(' ');
            if (checkbox.checked) {
              rel_attributes.push('noopener');
              Drupal.announce(Drupal.t('The noopener attribute has been added to rel.'));
            }
            else {
              rel_attributes = rel_attributes.filter((value) => value != 'noopener');
              Drupal.announce(Drupal.t('The noopener attribute has been removed from rel.'));
            }

            // Remove empty items.
            rel_attributes = rel_attributes.filter((value) => value.length);
            // Deduplicate items.
            rel_attributes = [...new Set(rel_attributes)];

            rel_attribute_field.value = rel_attributes.join(' ');
          })
        });
      }
    }
  };

}(Drupal, once, jQuery));
;
/**
 * @file
 * Some basic behaviors and utility functions for Linkit.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * @namespace
   */
  Drupal.linkit = {};

})(jQuery, Drupal, drupalSettings);
;
/**
 * @file
 * Linkit Autocomplete based on jQuery UI.
 */

(function ($, Drupal, once) {

  'use strict';

  var autocomplete;

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    var elementId = this.element.attr('id');

    if (!(elementId in autocomplete.cache)) {
      autocomplete.cache[elementId] = {};
    }

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      autocomplete.cache[elementId][term] = data.suggestions;
      response(data.suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.
    var term = request.term;

    // Check if the term is already cached.
    if (autocomplete.cache[elementId].hasOwnProperty(term)) {
      response(autocomplete.cache[elementId][term]);
    }
    else {
      var options = $.extend({
        success: sourceCallbackHandler,
        data: {q: term}
      }, autocomplete.ajax);
      $.ajax(this.element.attr('data-autocomplete-path'), options);
    }
  }

  /**
   * Handles an autocomplete select event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {boolean}
   *   False to prevent further handlers.
   */
  function selectHandler(event, ui) {
    var $form = $(event.target).closest('form');
    if (!ui.item.path) {
      throw 'Missing path param.' + JSON.stringify(ui.item);
    }

    $('input[name="href_dirty_check"]', $form).val(ui.item.path);

    if (ui.item.entity_type_id || ui.item.entity_uuid || ui.item.substitution_id) {
      if (!ui.item.entity_type_id || !ui.item.entity_uuid || !ui.item.substitution_id) {
        throw 'Missing path param.' + JSON.stringify(ui.item);
      }

      $('input[name="attributes[data-entity-type]"]', $form).val(ui.item.entity_type_id);
      $('input[name="attributes[data-entity-uuid]"]', $form).val(ui.item.entity_uuid);
      $('input[name="attributes[data-entity-substitution]"]', $form).val(ui.item.substitution_id);
    }

    event.target.value = ui.item.path;

    return false;
  }

  /**
   * Override jQuery UI _renderItem function to output HTML by default.
   *
   * @param {object} ul
   *   The <ul> element that the newly created <li> element must be appended to.
   * @param {object} item
   *  The list item to append.
   *
   * @return {object}
   *   jQuery collection of the ul element.
   */
  function renderItem(ul, item) {
    var $line = $('<li>').addClass('linkit-result-line');
    var $wrapper = $('<div>').addClass('linkit-result-line-wrapper');
    $wrapper.append($('<span>').html(item.label).addClass('linkit-result-line--title'));

    if (item.hasOwnProperty('description')) {
      $wrapper.append($('<span>').html(item.description).addClass('linkit-result-line--description'));
    }
    return $line.append($wrapper).appendTo(ul);
  }

  /**
   * Override jQuery UI _renderMenu function to handle groups.
   *
   * @param {object} ul
   *   An empty <ul> element to use as the widget's menu.
   * @param {array} items
   *   An Array of items that match the user typed term.
   */
  function renderMenu(ul, items) {
    var self = this.element.autocomplete('instance');

    var grouped_items = {};
    items.forEach(function (item) {
      const group = item.hasOwnProperty('group') ? item.group : '';
      if (!grouped_items.hasOwnProperty(group)) {
        grouped_items[group] = [];
      }
      grouped_items[group].push(item);
    })

    $.each(grouped_items, function (group, items) {
      if (group.length) {
        ul.append('<li class="linkit-result-line--group ui-menu-divider">' + group + '</li>');
      }

      $.each(items, function (index, item) {
        self._renderItemData(ul, item);
      });
    });
  }

  function focusHandler() {
    return false;
  }

  function searchHandler(event) {
    var options = autocomplete.options;

    return !options.isComposing;
  }

  /**
   * Attaches the autocomplete behavior to all required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the autocomplete behaviors.
   */
  Drupal.behaviors.linkit_autocomplete = {
    attach: function (context) {
      // Act on textfields with the "form-linkit-autocomplete" class.
      var $autocomplete = $(once('linkit-autocomplete', 'input.form-linkit-autocomplete', context));
      if ($autocomplete.length) {
        $.widget('custom.autocomplete', $.ui.autocomplete, {
          _create: function () {
            this._super();
            this.widget().menu('option', 'items', '> :not(.linkit-result-line--group)');
          },
          _renderMenu: autocomplete.options.renderMenu,
          _renderItem: autocomplete.options.renderItem
        });

        // Use jQuery UI Autocomplete on the textfield.
        $autocomplete.autocomplete(autocomplete.options);
        $autocomplete.autocomplete('widget').addClass('linkit-ui-autocomplete');

        $autocomplete.click(function () {
          $autocomplete.autocomplete('search', $autocomplete.val());
        });

        $autocomplete.on('compositionstart.autocomplete', function () {
          autocomplete.options.isComposing = true;
        });
        $autocomplete.on('compositionend.autocomplete', function () {
          autocomplete.options.isComposing = false;
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('linkit-autocomplete', 'input.form-linkit-autocomplete', context)
          .forEach((autocomplete) => $(autocomplete).autocomplete('destroy'));
      }
    }
  };

  /**
   * Autocomplete object implementation.
   */
  autocomplete = {
    cache: {},
    options: {
      source: sourceData,
      focus: focusHandler,
      search: searchHandler,
      select: selectHandler,
      renderItem: renderItem,
      renderMenu: renderMenu,
      minLength: 1,
      isComposing: false
    },
    ajax: {
      dataType: 'json'
    }
  };

})(jQuery, Drupal, once);
;
