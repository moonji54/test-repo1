# Managing the global subtypes list

1. Add taxonomy term reference like `field_content_label` your content type (machine name not important)
1. When creating the field, set the 'Reference method' to 'Content Label Taxonomy Term selection' and set it as
required. This prevents not-allowed content labels from showing (eg, Event for non-Event content types).
1. Use the 'Select list' field formatter in the Manage Form Display for each form mode

# Views subtype filter configuration

To have a content label filter with only a subset of the available subtypes:

1. Add an exposed filter to the content label field
1. Use the 'Content label categories to show content labels from' when editing the exposed filter to choose which Content Categories
   should apply.
