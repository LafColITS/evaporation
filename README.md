# Evaporation #
**Contributors:** mackensen  
**Tags:** cloudfront, cache  
**Requires at least:** 4.5  
**Tested up to:** 5.8.3  
**Requires PHP:** 5.6  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Evaporation is a cache invalidation plugin for WordPress sites that use AWS CloudFront as a full-site cache.

## Description ##

Evaporation is a cache invalidation plugin for WordPress sites that use AWS CloudFront as a full-site cache.

This plugin is derived from two other plugins: WP Super Cache and Carl Alexander's Ymir project. Note that it is
*not* a caching plugin but rather a cache invalidation plugin. It assumes that you are using AWS CloudFront for 
full-site caching and triggers invalidations for appropriate scenarios.

## Installation ##

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

## Frequently Asked Questions ##

### A question that someone might have ###

An answer to that question.

### What about foo bar? ###

Answer to foo bar dilemma.

## Screenshots ##

### 1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from ###
![This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from](http://ps.w.org/evaporation/assets/screenshot-1.png)

the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
### 2. This is the second screen shot ###
![This is the second screen shot](http://ps.w.org/evaporation/assets/screenshot-2.png)


## Changelog ##

### 1.0 ###
* A change since the previous version.
* Another change.

### 0.5 ###
* List versions from most recent at top to oldest at bottom.

## Upgrade Notice ##

### 1.0 ###
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

### 0.5 ###
This version fixes a security related bug.  Upgrade immediately.

## Arbitrary section ##

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

## A brief Markdown Example ##

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](https://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: https://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`
