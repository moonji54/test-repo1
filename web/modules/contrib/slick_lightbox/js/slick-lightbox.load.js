/**
 * @file
 * Provides SlickLightbox loader.
 */

(function ($, Drupal, drupalSettings, _db) {

  'use strict';

  var _unload = false;
  var _mounted = 'slick-lightbox-gallery--on';

  /**
   * SlickLightbox utility functions.
   *
   * @param {HTMLElement} elm
   *   The SlickLightbox gallery HTML element.
   */
  function doSlickLightbox(elm) {
    var sbox;
    var $slider = $('> .slick__slider', elm);
    var $container = $slider.length ? $slider : $(elm);
    var boxSettings = drupalSettings.slickLightbox.lightbox || {};
    var slickSettings = drupalSettings.slickLightbox.slick || {};

    boxSettings.itemSelector = boxSettings.itemSelector + ',[data-slick-lightbox-trigger]';
    var itemSelector = boxSettings.itemSelector;
    var $triggers = $(itemSelector, elm);

    function getIndex(item) {
      var i = 0;

      // Only refetch dynamic items with AJAX loaded contents such as IO, VIS.
      if (_unload) {
        $triggers = $container.find(itemSelector);
        _unload = false;
      }

      $triggers.each(function (idx, elm) {
        if (elm === item) {
          i = idx;
          return false;
        }
      });
      return i;
    }

    // Initializes slick with video supports.
    function initSlick(modalElement) {
      var media;
      var $box;
      var $media;
      var $player;
      var $video;
      var $slide;
      var $slick = $('.slick-lightbox-slick', modalElement);
      var $slides = $slick.children();
      var $instance;

      /**
       * Trigger the media close.
       */
      function closeOut() {
        var $playing = $slick.find('.is-playing');
        $video = $slick.find('video');

        // Clean up any pause marker at slider container.
        $slick.removeClass('is-paused');

        if ($playing.length) {
          $playing.removeClass('is-playing').find('.media__icon--close').click();
        }

        if ($video.length) {
          $video[0].pause();
        }
      }

      /**
       * Trigger pause on slick instance when playing a video.
       */
      function pause() {
        $slick.addClass('is-paused').slick('slickPause');
      }

      /**
       * Resize the responsive image.
       */
      function resizeImage() {
        var t = $(this);
        var w = t.width();
        var p = t.closest('.media-wrapper--inline');
        var c = p.next('.slick-lightbox-slick-caption');
        var ph = p.parent().height();

        if (c.length) {
          ph = ph - (c.height() + parseInt(c.css('margin-top').replace('px', '')));
        }

        t.css('max-height', ph);
        p.css({width: w});
      }

      /**
       * Build out the media player.
       *
       * @param {int} i
       *   The index of the current element.
       * @param {HTMLElement} box
       *   The gallery item HTML element which triggers the lightbox.
       */
      function buildOutMedia(i, box) {
        var html;
        $box = $(box);
        $slide = $($slides[i]);
        $player = $('.media--player', $slide);
        $video = $('video', $slide);
        media = $box.data('media');

        // Hook into native to fix potential lazy load issues.
        $('img', $slide).attr('loading', 'lazy');

        if (media) {
          $slide.addClass('slick-slide--' + media.type);
          // @todo remove bRich.
          var $html = media.html || $box.data('bRich') || false;
          if (media.type === 'rich' && $html && !$video.length) {
            try {
              html = 'html' in media ? $html : JSON.parse($html);
              $media = Drupal.theme('slickLightbox', {html: html, media: media});

              $slide.find('.slick-lightbox-slick-img').replaceWith($media);

              var $img = $slide.find('img');
              if ($img.length) {
                $img.addClass('slick-lightbox-slick-img');
                if ($slide.find('.slick-lightbox-slick-caption').length) {
                  window.setTimeout(function () {
                    $img.each(function () {
                      if (this.complete) {
                        resizeImage.call(this);
                      }
                      else {
                        $(this).one('load', resizeImage);
                      }
                    });
                  }, 10);
                }
              }
            }
            catch (e) {
              // Ignore.
            }

            Drupal.attachBehaviors($video[0]);
          }
          else {
            if ($box.data('boxUrl') && !$player.length) {
              // @todo replace when Blazy branches have it.
              // @todo $media = Drupal.theme('blazyMedia', {el: box});
              $media = Drupal.theme('slickLightboxMedia', {el: $box});

              $slide.find('.slick-lightbox-slick-img').replaceWith($media);

              Drupal.attachBehaviors($player[0]);
            }
          }
        }
      }

      // Initializes slick.
      if (!$slick.hasClass('slick-initialized')) {
        $triggers.each(buildOutMedia);

        $instance = $slick.slick(slickSettings);

        $slick.on('afterChange.slbox', closeOut);
        $slick.on('click.slbox', '.media__icon--close', closeOut);
        $slick.on('click.slbox', '.media__icon--play', pause);

        // Fix for broken display when only has 1 slide, like usual.
        if ($slides.length === 1) {
          var slick = $slick.slick('getSlick');
          $slick.addClass('slight-lightbox-slick--unslick');
          slick.$slideTrack.css({left: '', transform: ''});
        }
      }

      return $instance;
    }

    var options = {
      // Prevents clicking a video player button from closing the lightbox.
      // @todo re-enable when the library provides a fix for this.
      closeOnBackdropClick: false,
      itemSelector: itemSelector,
      caption: function (target, info) {
        var $caption = $(target).next('.litebox-caption');
        return $caption.length ? $caption.html() : '';
      },
      src: function (target) {
        var $target = $(target);
        return $target.data('boxUrl') || $target.attr('href');
      },
      slick: initSlick
    };

    var events = {
      'show.slickLightbox': function () {
        // Prevents media player with aspect ratio from being squeezed.
        $('.slick-slide--video .slick-lightbox-slick-item-inner').removeAttr('style');

        // Overrides closeOnBackdropClick as otherwise clicking video play
        // button closes the entire lightbox.
        // @todo remove when the library fixes this.
        $($container[0].slickLightbox.$modalElement).on('click.slbox', '.slick-lightbox-slick-item', function (e) {
          if (e.target === this) {
            $('.slick-lightbox-close').click();
          }
        });
      }
    };

    // Initializes slick lightbox.
    var boxOptions = boxSettings ? $.extend({}, options, boxSettings) : options;

    /**
     * Open the lightbox, and sets the correct delta.
     *
     * @todo remove when the library provides index argument to its initSlick().
     */
    function open() {
      var $this = $(this);
      var delta = 0;
      var found = false;
      var containers = boxSettings.itemContainers;

      // Delta is set per field which is always 0 within a view, fix it.
      if (containers) {
        var len = containers.length;

        for (var i = 0; i < len; i++) {
          var $item = $this.closest(containers[i]);
          if ($item.length) {
            delta = $item.index();
            found = true;
            break;
          }
        }
      }

      // Assumes anything else such as multi-entity blazy, or slick.
      if (!found) {
        delta = getIndex($this[0]);
      }

      sbox = $container[0].slickLightbox;
      if (sbox && sbox.slick) {
        sbox.slick.slick('slickGoTo', delta, true);
      }
    }

    // Init the lightbox.
    $container.slickLightbox(boxOptions).on(events);

    // @todo remove when the library provides index argument to its initSlick().
    $container.off('click.slbox').on('click.slbox', itemSelector, open);
    $(elm).addClass(_mounted);
  }

  /**
   * Theme function for a blazy PhotoSwipe (Remote|local) video.
   *
   * @param {Object} settings
   *   An object with the following keys:
   * @param {Array} settings.item
   *   The array of item properties containing: media and html.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.slickLightbox = function (settings) {
    var media = settings.media;
    var html;

    html = '<div class="media-wrapper media-wrapper--inline" style="width:' + media.width + 'px">';
    html += settings.html;
    html += '</div>';

    return html;
  };

  /**
   * Theme function for a lightbox video.
   *
   * @param {Object} settings
   *   An object containing the link element which triggers the lightbox.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   *
   * @todo replace with Drupal.theme.blazyMedia() when Blazy branches have it.
   */
  Drupal.theme.slickLightboxMedia = function (settings) {
    var $elm = settings.el;
    var media = $elm.data('media');
    var alt = $('img', $elm).length ? $('img', $elm).attr('alt') : '';
    var pad = ((media.height / media.width) * 100).toFixed(2);
    var boxUrl = $elm.data('boxUrl');
    var oembedUrl = $elm.data('oembedUrl') ? $elm.data('oembedUrl') : $elm.attr('href');
    var html;

    html = '<div class="media-wrapper media-wrapper--inline" style="width:' + media.width + 'px">';
    html += '<div class="media media--switch media--player media--ratio media--ratio--fluid" style="padding-bottom: ' + pad + '%">';
    html += '<img src="' + boxUrl + '" class="media__image media__element" alt="' + Drupal.t(alt) + '"/>';
    html += '<span class="media__icon media__icon--close"></span>';
    html += '<span class="media__icon media__icon--play" data-url="' + oembedUrl + '"></span>';
    html += '</div></div>';

    return html;
  };

  /**
   * Attaches Slick Lightbox gallery behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickLightbox = {
    attach: function (context) {

      // Context is unreliable with AJAX contents like product variations, etc.
      context = context instanceof HTMLDocument ? context : document;

      // @todo use core/once for 2.x. The dBlazy.once is to keep D8 - D10 which
      // is not possible if using core/once for now till 2.x.
      var _gallery = '[data-slick-lightbox-gallery]:not(.' + _mounted + '), .slick-lightbox-gallery:not(.' + _mounted + ')';
      var check = context.querySelector(_gallery);
      var items = check === null ? [] : context.querySelectorAll(_gallery);
      if (items.length) {
        _db.once(_db.forEach(items, doSlickLightbox, context));
      }
    },
    detach: function (context, settings, trigger) {
      _unload = trigger === 'unload';
    }
  };

})(jQuery, Drupal, drupalSettings, dBlazy);
