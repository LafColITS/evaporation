---
# Feel free to add content and custom Front Matter to this file.
# To modify the layout, see https://jekyllrb.com/docs/themes/#overriding-theme-defaults

layout: home
---

Evaporation is a cache invalidation plugin for WordPress sites that use AWS CloudFront as a full-site cache.

This plugin is inspired by two other projects: the [Super Page Cache for Cloudflare plugin](https://wordpress.org/plugins/wp-cloudflare-page-cache/) and Carl Alexander's [Ymir project](https://ymirapp.com/). Note that it is *not* a caching plugin but rather a cache invalidation plugin. It assumes that you are using AWS CloudFront for full-site caching and triggers invalidations for appropriate scenarios.

Please see [CloudFront](/cloudfront/) for a description of our CloudFront distribution. It's based on the [AWS reference architecture for WordPress](https://docs.aws.amazon.com/whitepapers/latest/best-practices-wordpress/reference-architecture.html) but revised for explicit multisite support and to incorporate Caching and Origin request policies.