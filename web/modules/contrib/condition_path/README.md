CONDITION PATH
==============

## CONTENTS OF THIS FILE

* Introduction
* Requirements
* Installation
* Configuration
* Usage
* Maintainers

## INTRODUCTION

Extends condition plugin API to add included and excluded paths to control the
content visibility.
Drupal core conditional plugin API provides the ability to control
the visibility of content (block) using paths, etc.
Drupal core has its own path plugin which only allows whitelisted pages OR
blacklisted pages. Not both at the same time. This is where this module comes
in. It provides a new condition plugin built upon the core path condition plugin
which allows both included and excluded (sub) paths.

This plugin can be used to show or hide all kinds of content which implement the
conditions plugins, such as blocks.


## REQUIREMENTS

None.


## INSTALLATION

* Install as usual, see http://drupal.org/node/1897420 for further information.


## CONFIGURATION

* Configure visibility of block: Administration » Structure » Block layout »
  Configure block » Under Visibility add/update "Request Path Include Exclude"
* Other types of content within Drupal might also use Conditions such as Rules
  and Page Manager, so it's not just restricted to blocks.


## USAGE

Fill in the pages or negated pages in the condition form, each one on a new line.
The more specific the page, the lower in the list it has to be placed.
(Note that some examples below are encoded for Markdown markup)

1) Show block on the news overview page and all news subpages except on the news comments pages:
   * /news
   * /news/*
   * !/news/*/comments

2) Show block on pages item1 and item3 and hide on pages item2, item4 and all subpages of item4:
   * /item1
   * /item1/*
   * !/item1/item2
   * !/item1/item2/item3/item4
   * !/item1/item2/item3/item4/*

3) The same result as example 2:
   * /item1
   * /item1/item2/item3

4) Show block only on the frontpage:
   * \<front\>

5) Show block on all pages, except on news article pages but with news comment pages included:
   * \*
   * !/news/*
   * /news/*/comments

Extra tips:
* Both path aliases and internal paths can be used.
* Try to group the include and exclude paths as much as you can for performance.


## MAINTAINERS

* Lennart Van Vaerenbergh (Fernly) - https://www.drupal.org/u/fernly
