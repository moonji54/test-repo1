
# <a name="top"> </a>CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Features](#features)
 * [Lightbox optionset & skin](#optionset)
 * [Troubleshooting](#troubleshooting)
 * [Maintainers](#maintainers)

***
***
# <a name="introduction"></a>INTRODUCTION

Provides a simple integration with Slick Lightbox.

***
***
# <a name="requirements"> </a>REQUIREMENTS
* Slick Lightbox library:
  + Download Slick Lightbox archive from
    [Slick Lightbox](https://github.com/mreq/slick-lightbox)
  + Extract it as is, rename **slick-lightbox-master** to **slick-lightbox"**,
    so the asset is available at:

    **/libraries/slick-lightbox/dist/slick-lightbox.min.js**
    **/libraries/slick-lightbox/dist/slick-lightbox.css**

    You can remove non-essential files, except these two files.

* [Slick](https://drupal.org/project/slick), 2.x+ recommended.


***
***
# <a name="installation"> </a>INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)


***
***
# <a name="configuration"> </a>CONFIGURATION
Enable this module and its dependency, core image and Blazy modules.

## FIELD FORMATTERS
* **/admin/config/people/accounts/fields**, or **/admin/structure/types**,
  or any fieldable entity, click **Manage display**.
* Under **Format**, choose blazy-related formatters:
  **Blazy**, **Slick carousel**, etc. for image field.
* Click the **Configure** icon.
* Under **Media switcher**, choose **Image to Slick Lightbox**. Adjust the rest.

## BLAZY FILTER
* **/admin/config/content/formats/full_html**, etc.
* Enable **Blazy Filter**.
* Under **Media switcher**, choose **Image to Slick Lightbox**.


***
***
# <a name="features"></a>FEATURES
* Has no formatter, instead integrated into **Media switcher** option as seen at
  Blazy/ Slick formatters, including Blazy Views fields for File Entity and
  Media, and also Blazy Filter for inline images.
* Swipeable video for core Media module.


***
***
# <a name="optionset"></a>SLICK LIGHTBOX OPTIONSET & SKIN
Enable Slick UI sub-module first, otherwise regular **Access denied**.
We only have one optionset for the Slick Lightbox, override it accordingly:

**/admin/config/media/slick/list/slick_lightbox/edit**

You can select a skin you registered yourself under `Skin` option here. To
register skins, check out [Slick FAQ](/admin/help/slick_ui#faq) about `Skins`.
Be aware regular inline Slick markups are different from lightbox ones for the
most part, except few like arrows, etc.

This module doesn't currently offer a specialized skin, except `Default` to
avoid totally broken display at the first look.

***
***
## <a name="troubleshooting"></a>TROUBLESHOOTING
Has no skins, yet. Patches or contributions are very much welcome to improve the
first looks like what Colorbox has OOTB.

Good at CSS? We welcome your contributions to make this Slick Lightbox slick,
even if you don't patch, contributing just a CSS attachment is very much
welcome. We can provide UI options later once we have some skins. Until then,
you can select a skin you registered yourself under `Skin` option at `Slick
Lightbox` optionset which is currently left empty, fallback to `Default`.


## SIMILAR MODULES
[Colorbox](https://drupal.org/project/colorbox)


***
***
# <a name="maintainers"> </a>MAINTAINERS/CREDITS
* [Gaus Surahman](https://drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/2547553/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

[Slick Lightbox module](http://drupal.org/project/slick_lightbox)

See the Slick Lightbox docs at:

* [Slick Lightbox at Github](https://github.com/mreq/slick-lightbox)
* [Slick Lightbox website](http://mreq.github.io/slick-lightbox/)
