<?php
/**
 * Class which encapsulates basic helper functions.
 *
 * @package Evaporation
 * @since 0.0.1
 */

namespace Evaporation;

require 'vendor/autoload.php';

use Aws\CloudFront\CloudFrontClient;

/**
 * Base functions.
 *
 * @since 0.0.1
 */
class Base {
    /**
     * Register hooks for invalidation.
     */
    protected static function init_action() {
        // Hook for invalidating posts.
        add_action( 'wp_transition_post_status', array( 'Evaporation\Base', 'changed_post' ), 0, 3 );

        // Hooks for site-wide invalidation.
        add_action( 'switch_theme', array( 'Evaporation\Base', 'changed_site' ), 0 );
        add_action( 'permalink_structure_changed', array( 'Evaporation\Base', 'changed_site' ), 0 );
        add_action( 'customize_save_after', array( 'Evaporation\Base', 'changed_site' ), 0 );
        add_action( 'update_option_theme_mods_' . get_stylesheet(), array( 'Evaporation\Base', 'changed_site' ), 0 );
    }

    /**
     * Queue invalidation for a changed post.
     *
     * @param string $new_status
     * @param string $old_status
     * @param {WP_POST} $post
     */
    protected static function changed_post( $new_status, $old_status, $post ) {
         $type = is_object( $post ) ? get_post_type_object( $post->post_type ) : null;
        if ( empty( $type ) ) {
            return;
        }

        // Only invalidate public pages.
        if ( ! $type->public ) {
            return;
        }

        // Do not invalidate auto-drafts.
        if ( 'auto-draft' === $new_status ) {
            return;
        }

        self::invalidate_post( $post );
    }

    /**
     * Invalidate a post.
     */
    protected static function invalidate_post( $post ) {
        $permalink = get_permalink( $post );

        // Sanity checking.
        if ( ! $post instanceof \WP_Post
            || ! is_string( $permalink )
            || ! in_array( $post->post_status, ['publish', 'private', 'trash', 'pending', 'draft'], true)
            || in_array( $post->post_type, ['nav_menu_item', 'revision'], true )
        ) {
            return;
        }

        // Sanitize permalink for trashed post.
        if ('trash' === $post->post_status) {
            $permalink = str_replace('__trashed', '', $permalink);
        }

        // @TODO: Build array of all related content.
    }

    protected static function changed_site() {

    }

    protected static function get_aws_client() {
        $cloudfront_client = new CloudFrontClient([
            'profile' => 'default',
            'region' =>  self::get_aws_region(),
            'version' => '2020-05-31',
        ]);
        return $cloudfront_client;
    }

    protected static function get_aws_region() {
        if ( AWS_DEFAULT_REGION ) {
            return AWS_DEFAULT_REGION;
        }
        if ( getenv( 'AWS_DEFAULT_REGION' ) ) {
            return getenv( 'AWS_DEFAULT_REGION' );
        }
        return 'us-east-1';
    }

    protected static function get_distribution_id() {
        if ( EVAPORATION_DISTRIBUTION_ID ) {
            return EVAPORATION_DISTRIBUTION_ID;
        }
        if ( getenv( 'EVAPORATION_DISTRIBUTION_ID' ) ) {
            return getenv( 'EVAPORATION_DISTRIBUTION_ID' );
        }
        return null;
    }

    public static function invalidate_site( $caller_reference = '' ) {
        // Create the caller reference if not set.
        if ( ! $caller_reference ) {
            $caller_reference = time();
        }

        // Get the client.
        $client = self::get_aws_client();
        $result = $client->createInvalidation([
            'DistributionId' => self::get_distribution_id(),
            'InvalidationBatch' => [
                'CallerReference' => $caller_reference,
                'Paths' => [
                    'Items' => ['/*'],
                    'Quantity' => 1,
                ],
            ],
        ]);
    }
}