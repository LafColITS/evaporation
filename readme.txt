=== Evaporation ===
Contributors: mackensen
Tags: cloudfront, cache
Requires at least: 6.2
Tested up to: 6.2.3
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Evaporation is a cache invalidation plugin for WordPress sites that use AWS CloudFront as a full-site cache.

== Description ==

Evaporation is a cache invalidation plugin for WordPress sites that use AWS CloudFront as a full-site cache.

This plugin is inspired by two other projects: the [Super Page Cache for Cloudflare plugin](https://wordpress.org/plugins/wp-cloudflare-page-cache/) and Carl Alexander's [Ymir project](https://ymirapp.com/). Note that it is *not* a caching plugin but rather a cache invalidation plugin. It assumes that you are using AWS CloudFront for full-site caching and triggers invalidations for appropriate scenarios.

== Installation ==

1. Upload the `evaporation` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Define `EVAPORATION_DISTRIBUTION_ID` in your `wp-config.php` with the ID of your CloudFront distribution.
1. Define `AWS_DEFAULT_REGION` in your `wp-config.php`. If you do not, `us-east-1` is assumed.

If you're cloning from GitHub you'll need to run a `composer install` to install the required AWS SDK packages.

== Frequently Asked Questions ==

= How does the plugin authenticate against the Amazon API? =

The plugin assumes that you are hosting WordPress on AWS, in either a container or EC2 instance, and that the environment has permission to create an invalidation on your CloudFront distribution. Evaporation does not support using separate IAM keys. The only necessary permission is `cloudfront:CreateInvalidation`.

= Can editors trigger an invalidation from wp-admin? =

There are no controls for manually triggering a cache invalidation from the admin pages, either individually or for the full site.

= Does this plugin support multisite? =

Yes! However, there is a caveat for subdomain multisite environments. CloudFront allows you to [incorporate headers](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/distribution-web-values-specify.html#DownloadDistValuesForwardHeaders) to your caching strategy. Therefore, if you pass the `Host` header, you can store cached pages for multiple domains on a single CloudFront distribution, even if they have the same paths. However, CloudFront [does not support](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/Invalidation.html) headers when creating invalidations. For example, if your distribution is caching baz.example.org and foo.example.org, and both domains have a page named `/staff/`, creating an invalidation for /staff/ will invalidate that page on both domains. This is a limitation on the AWS side.

== Hooks and filters ==
= evaporation_related_urls =
The `evaporation_related_urls` filter allows you to add URLs to be invalidated with a given post. It expects two arguments:

* `$urls`: array of related URLs to be invalidated
* `$post`: the `WP_Post` object representing the post

== WP-CLI integration ==
The plugin includes a single WP-CLI command for invalidating a post:

```
# Invalidate a post
wp evaporation invalidate post 8082
Invalidation created for /author/somebody/
Invalidation created for /author/somebody/feed/
Invalidation created for /2024/03/28/your-post/
Invalidation created for /category/your-category/
Invalidation created for /
Success: Invalidation ID IDBP36WW3PLXW6WD3AC5UQJRF5 created
```

== Changelog ==

= Unreleased =
* Initial public push

== License ==
Evaporation plugin code is licensed under GPLv2 or later. The AWS SDK for PHP is copyright Amazon.com, Inc and distributed under the Apache License, version 2.0. 
