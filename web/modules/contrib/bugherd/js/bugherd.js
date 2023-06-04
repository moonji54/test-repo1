/**
 * @file
 * Init Bugherd with the project key and config.
 */

 var BugHerdConfig = undefined;

 (function ($, Drupal) {

  'use strict';

  $(document).ready(function () {

      var project_key = drupalSettings.bugherd.project_key;

      // Define debugherd configuration.
      BugHerdConfig = drupalSettings.bugherd.bugherdconfig;

      // Insert bugherd script.
      (function (d, t) {
        var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
        bh.type = 'text/javascript';
        bh.src = '//www.bugherd.com/sidebarv2.js?apikey='+project_key;
        s.parentNode.insertBefore(bh, s);
      })(document, 'script');

  });
})(jQuery, Drupal);
