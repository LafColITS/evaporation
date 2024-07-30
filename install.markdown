---
layout: page
title: Install
permalink: /install/
---

# Installation


- Upload the evaporation folder to the `/wp-content/plugins/` directory
- Activate the plugin through the 'Plugins' menu in WordPress
- Define `EVAPORATION_DISTRIBUTION_ID` in your `wp-config.php` with the ID of your CloudFront distribution.
- Define `AWS_DEFAULT_REGION` in your `wp-config.php`. If you do not, `us-east-1` is assumed.

If you're cloning from GitHub you'll need to run a `composer install` to install the required AWS SDK packages.

# Authentication

The plugin assumes that you are hosting WordPress on AWS, in either a container or EC2 instance, and that the environment has permission to create an invalidation on your CloudFront distribution. Evaporation does not support using separate IAM keys. The only necessary permission is `cloudfront:CreateInvalidation`.