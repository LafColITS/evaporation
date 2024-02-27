<?php
/**
 * Class which encapsulates basic helper functions.
 *
 * @package Evaporation
 * @since 0.0.1
 */

namespace Evaporation;

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
    public static function init_action() {
        $self = new self();

        // Hook for invalidating posts.
        add_action( 'transition_post_status', array( $self, 'changed_post' ), 0, 3 );

        // Hooks for site-wide invalidation.
        add_action( 'switch_theme', array( $self, 'changed_site' ), 0 );
        add_action( 'permalink_structure_changed', array( $self, 'changed_site' ), 0 );
        add_action( 'customize_save_after', array( $self, 'changed_site' ), 0 );
        add_action( 'update_option_theme_mods_' . get_stylesheet(), array( $self, 'changed_site' ), 0 );
    }

    /**
     * Queue invalidation for a changed post.
     *
     * @param string $new_status
     * @param string $old_status
     * @param {WP_POST} $post
     */
    public static function changed_post( $new_status, $old_status, $post ) {
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

        // Sanity checking.
        if ( ! $post instanceof \WP_Post
            || ! is_string( get_permalink( $post ) )
            || ! in_array( $post->post_status, ['publish', 'private', 'trash', 'pending', 'draft'], true)
            || in_array( $post->post_type, ['nav_menu_item', 'revision'], true )
        ) {
            return;
        }

        $permalink = get_permalink( $post );

        // Sanitize permalink for trashed post.
        if ('trash' === $post->post_status) {
            $permalink = str_replace('__trashed', '', $permalink);
        }

        // Get the actual relative URL for invalidation.
        $url_to_invalidate = wp_make_link_relative( $permalink );

        self::invalidate_post( [ $url_to_invalidate ] );
    }

    /**
     * Invalidate a post.
     */
    public static function invalidate_post( $urls, $caller_reference = '' ) {
        // Create the caller reference if not set.
        if ( ! $caller_reference ) {
            $caller_reference = self::get_caller_reference( $urls );
        }

        self::write_log( "Invalidation for " . json_encode($urls) . " with caller reference {$caller_reference}" );

        // Get the client.
        $client = self::get_aws_client();
        $result = $client->createInvalidation([
            'DistributionId' => self::get_distribution_id(),
            'InvalidationBatch' => [
                'CallerReference' => $caller_reference,
                'Paths' => [
                    'Items' => $urls,
                    'Quantity' => count( $urls ),
                ],
            ],
        ]);
    }

    protected static function get_aws_client() {
        $cloudfront_client = new CloudFrontClient([
            'region' =>  self::get_aws_region(),
            'version' => '2020-05-31',
        ]);
        return $cloudfront_client;
    }

    protected static function get_aws_region() {
        if ( defined( AWS_DEFAULT_REGION ) ) {
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

    protected static function get_caller_reference( $urls = [] ) {
        if ( ! $urls ) {
            return time();
        } else {
            return time() . '_' . hash( 'sha256', json_encode( $urls ) );
        }
    }

    public static function invalidate_site( $caller_reference = '' ) {
        // Create the caller reference if not set.
        if ( ! $caller_reference ) {
            $caller_reference = self::get_caller_reference();
        }

        // Debug.
        self::write_log( "Full site invalidation with caller reference {$caller_reference} ");

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

    protected static function write_log( $log ) {
        if ( true === WP_DEBUG ) {
            error_log( $log );
        }
    }
}