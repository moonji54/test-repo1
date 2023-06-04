# General instructions

## Initial setup (backend build).

1. Login to browserless.io and copy the API key from the dashboard.
1. Add the `$settings['browserless_api_key']` into your settings.local.php
1. Configure the Node Type to have a Media field referencing the Document Media Type
1. Configure the Node Type to enable PDF Generation
1. Create a `page__pdf.html.twig` in your theme with just `{{ page.content }}`
1. Exclude any blocks that aren't necessary from block layout.
[Condition Path](https://www.drupal.org/project/condition_path) using /node/*/pdf can help.
1. Make an alternative html--pdf.html.twig if needed to stip out unnecessary things from there.
1. Set up your page size an scaling

Sample page size and scaling:
```php
/**
 * Alter request option before sending to client API.
 */
function MY_MODULE_soapbox_pdf_request_alter($url, array &$options, $type) {
  $options['json']['options']['format'] = 'A4';
  $options['json']['options']['scale'] = 1.224;
  $options['json']['options']['displayHeaderFooter'] = FALSE;
  $options['json']['options']['printBackground'] = TRUE;
}
```
The above shows some example options. Only keep what you need, don't 
blindly copy-paste please.

### If you are having scaling issues.

Try the above `'scale'`.
Try adding this print style:

```scss
@media print {
  html, body {
    min-height : 297mm;
    min-width  : 270mm;
  }
}
```

## Local setup for PDF generation in DDEV (once backend build is complete).

1. Login to browserless.io and copy the API key from the dashboard.
1. Add the `$settings['browserless_api_key']` into your settings.local.php
1. Start NGROK to provide a public URL for your local `ddev share`
1. Copy the URL to your settings.local.php, eg `$settings['ngrok_url'] = 'https://775cb404.ngrok.io';`
1. If your site has `$settings['trusted_host_patterns']` (hosting dependant), add your ngrox URL via settings.local.php,
eg `$settings['trusted_host_patterns'][] = '^775cb404\.ngrok\.io$'`
1. Run the PDF generation script.

# Example

## Example node__pdf.html.twig

```twig
{{ attach_library('THEMENAME/pdf-styling') }}

<header class="c-pdf__header">
  <div class="c-pdf__header-inner">
    Test header • <span class="c-pdf__page-number">Test123</span>
  </div>
</header>

{% set wysiwyg = content.body|render %}
<div>

  <div class="c-pdf__cover">
    <h1>{{ label }}</h1>
    <p>Nulla facilisi. Sed mollis, eros et ultrices tempus, mauris ipsum aliquam libero, non adipiscing dolor urna a
      orci. Etiam imperdiet imperdiet orci. Etiam vitae tortor.</p>
  </div>

  <div class="c-pdf__body">
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
    {{ wysiwyg }}
  </div>

</div>


<footer class="c-pdf__footer">
  <div class="c-pdf__footer-inner">
    Test footer • <span class="c-pdf__page-number">Test</span>
  </div>
</footer>
```

## Example THEMENAME.libraries.yml

```yml
pdf-styling:
  version: 1.0
  css:
    theme:
      assets/css/pdf-style.min.css: { minified: true }
```

## Example pdf-style.scss

```scss
.c-pdf {

  &__header {
    background-color: lightskyblue;

    // Mark the header as having a custom PagedJS position.
    position: running(headerRunning);
  }

  &__page-number {
    content: counter(page);
  }

  &__cover {
    background-color: lightsalmon;

    // Mark the cover div as a specific unique page layout.
    page: coverPage;
  }

  &__body {
    // Reset the page number when this class is first hit.
    // Yep, it can be anything!
    counter-reset: page 999;
    line-height: 2rem;

    // Mark the body div as a specific unique page layout.
    page: bodyPage;
  }

  &__footer {
    background-color: lightcoral;
    // Mark the footer as having a custom PagedJS position.
    position: running(footerRunning);
  }
}

// Position the header into top-center and footer into bottom right.
// Target a named paged specifically to avoid this applying to
// pages like the cover page, final page, etc.
// @see https://www.pagedjs.org/documentation/07-generated-content-in-margin-boxes/
@page bodyPage {

  // Set a custom margin size.
  // In this case, 60mm header, 20mm footer,
  // 30mm left and right side.
  margin: 60mm 30mm 20mm 30mm;

  // Control specific margin areas, adding
  // named content from above into them.
  @top-center {
    content: element(headerRunning);
  }
  @top-right {
    content: counter(page);
  }
  @bottom-right {
    content: element(footerRunning);
  }
}

// Customisations to the cover page.
.pagedjs_first_page {
  .pagedjs_pagebox {

    // Remove all margin areas by effectively deleting the
    // grid sizes. Yep, you can do things like this per page.
    // You can also target :first page, :nth page etc.
    grid-template-columns: 0;
    grid-template-rows: 0;

    // Add a background to make this clear on PDF render.
    background-color: lightblue;
  }
}

// Customisations to the generated PagedJS content, eg to apply css
// to the entire header.
// In this case, to bodyPage only (named page from above).
.pagedjs_bodyPage_page {
  .pagedjs_margin-top-left-corner-holder,
  .pagedjs_margin-top-right-corner-holder {
    background-color: lightsalmon;
  }

  .pagedjs_margin-top {
    background-color: lightsalmon;
  }
}
```
