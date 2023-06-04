# Context Active Trail

Context Active Trail sets the active trail and breadcrumbs for a page based on
the context it is in. For example, you can make every node of type _article_
appear to live under the _Blog_ menu item.

* For a full description of the module, visit the
  [project page](https://www.drupal.org/project/context_active_trail).

* Submit bug reports, suggestions, or track changes on the
  [Context Active Trail issue page](https://www.drupal.org/project/issues/2798989).


## Requirements

This module requires the following modules:

 * Context (https://drupal.org/project/context)


## Recommended modules

 * Context UI, part of Context, allows this module to be configured in the
   user interface.


## Installation

 * Install [as you would normally install a contributed Drupal module]
(https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules).

 * This module is not compatible with other modules that attempt to take
   control of active trails, such as:

    * [Menu Trail By Path](https://www.drupal.org/project/menu_trail_by_path)


## Configuration

With Context UI installed, create or modify contexts at
Administration » Structure » Contexts.

* For each context, you may add a Reaction of the type Active Trail.
* Choose a menu item to set the active trail of matching requests,.
* Optionally enable setting breadcrumbs as well.


## Development

To ease dev env setup, a Lando file has been included with the module. Here's how
you can use it to participate in the development of this module.

* Clone the [module's code repository](https://git.drupalcode.org/project/context_active_trail).
* `cd` into the `sandbox` directory.
* Run `lando start` (Lando must be installed).
* Run `lando prepare` to prepare a sandbox Drupal site.

You can now work on the module in the following directory:

    sandbox/web/modules/contrib/context_active_trail

See [Lando documentation](https://docs.lando.dev/) for more info on Lando.
Run `lando help` for a list of Lando commands.


## Acknowledgements

* [Evolving Web](https://evolvingweb.ca) - Sponsorship for initial development.
* [Dave Vasilevsky (vasi)](https://github.com/vasi) - Initial development.
* [Jigar Mehta (jigarius)](https://jigarius.com/about) - Current maintainer.
