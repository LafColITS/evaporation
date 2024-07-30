---
layout: page
title: CloudFront
permalink: /cloudfront/
---

This page describes a compatible CloudFront configuration. Your mileage may vary.

## Distribution

The distribution itself doesn't require a significant amount of configuration; the real complexity is in the behaviors. You'll want to associate an AWS WAF for things like bot protection.

## Behaviors

Each behavior you create in CloudFront is mapped to a different path. This is how you vary the behavior between`/wp-admin` (no caching), `/wp-content` (all the caching), and `/*` (most of the caching).

A CloudFront distribution for a subdirectory multisite needs 11 different behaviors. This is because we need to account for the subsites: `/wp-admin/*` will not capture the wp-admin for a subsite, but `/*/wp-admin/*` will.

Behaviors:

- `/wp-content/*` and `/*/wp-content/*`:
    - Viewer protocol policy: Redirect HTTP to HTTPS
    - Allowed HTTP methods: GET, HEAD, OPTIONS
    - Cache policy: WpContent
    - Origin request policy: Content
- `/wp-includes/*`
- `/*/wp-includes/*`
    - Viewer protocol policy: Redirect HTTP to HTTPS
    - Allowed HTTP methods: GET, HEAD, OPTIONS
    - Cache policy: WpContent
    - Origin request policy: Content
- `/wp-admin/*`
- `/*/wp-admin/*`
    - Viewer protocol policy: Redirect HTTP to HTTPS
    - Allowed HTTP methods: GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE
    - Cache policy: Disabled
    - Origin request policy: WpAdmin
- `/wp-login.php`
- `/*/wp-login.php`
    - Viewer protocol policy: Redirect HTTP to HTTPS
    - Allowed HTTP methods: GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE
    - Cache policy: Disabled
    - Origin request policy: WpLogin
- `/wp-json/*`
- `/*/wp-json/*`
    - Viewer protocol policy: Redirect HTTP to HTTPS
    - Allowed HTTP methods: GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE
    - Cache policy: Disabled
    - Origin request policy: WpAdmin
- `Default (*)`
    - Viewer protocol policy: Redirect HTTP to HTTPS
    - Allowed HTTP methods: GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE
    - Cache policy: Default
    - Origin request policy: Default

## Cache policies

A Cache policy defines how content is stored in the CloudFront cache after being retrieved from the Origin. We have two custom policies. We use a managed AWS policy, CachingDisabled, for wp-admin, wp-json, and wp-login.php.

- **WpContent**
    - TTL settings
        - Maximum TTL: 604800 seconds
        - Default TTL: 86400 seconds
    - Cache key settings:
        - Headers: Origin, Host
        - Cookies: None
        - Query strings: All
- **Default**
    - TTL settings
        - Maximum TTL: 604800 seconds
        - Default TTL: 86400 seconds
    - Cache key settings:
        - Headers: Origin, Host
        - Cookies:
            - `comment_author_*`
            - `wp-postpass*`
            - `comment_author_email_*`
            - `wp_settings-*`
            - `comment_author_url_*`
            - `wordpress_*`
            - `wordpress_test_cookie_*`
            - `wordpress_logged_in_*`
            - `wordpress_sec_*`
        - Query strings: All

## Origin request policies

An Origin request policy defines which headers, cookies, and query strings are passed by a Behavior to the Origin. We have four:

- **Content**: passes the Origin, X-WP-Nonce, and Host headers. No cookies and no query strings.
- **WpLogin**: passes the Origin, Referer, X-WP-Nonce, and Host headers. Passes all query strings and the following cookies:
  - `comment_author_*`
  - `wp-postpass*`
  - `comment_author_email_*`
  - `wp_settings-*`
  - `comment_author_url_*`
  - `wordpress_*`
  - `wordpress_test_cookie_*`
  - `wordpress_logged_in_*`
  - `wordpress_sec_*`
- **WpAdmin** and **Default**: passes the Origin, User-Agent, X-WP-Nonce, and Host headers. Passes all query strings and the same cookies as WpLogin.

WpAdmin and Default used to differ until we discovered that without the `User-Agent` header the rich-text editor doesn't work properly, and that might be a valid use case for Default if you have a public Gravity Form that exposes the editor.