<?php
/**
 * Class which encapsulates basic helper functions.
 *
 * @package Evaporation
 * @since 0.0.1
 */

namespace Evaporation;

use Aws\CloudFront\CloudFrontClient;
use Aws\CloudFront\Exception\CloudFrontException;
use Aws\Exception\CredentialsException;

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
	 * @param string   $new_status The new status for the post.
	 * @param string   $old_status The old status for the post.
	 * @param \WP_Post $post The post object.
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
		if ( 'auto-draft' === $new_status || ( 'auto-draft' === $old_status && 'draft' === $new_status ) ) {
			return;
		}

		// Do not invalidate edits to unpublished posts.
		if ( $new_status === $old_status && ( 'publish' !== $new_status ) ) {
			return;
		}

		if ( is_int( wp_is_post_autosave( $post ) ) || is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}

		// Sanity checking.
		if ( ! $post instanceof \WP_Post
			|| ! is_string( get_permalink( $post ) )
			|| ! in_array( $post->post_status, array( 'publish', 'private', 'trash', 'pending', 'draft' ), true )
			|| in_array( $post->post_type, array( 'nav_menu_item', 'revision' ), true )
		) {
			return;
		}

		self::invalidate_post( $post );
	}

	/**
	 * Queue invalidation for the entire site.
	 */
	public static function changed_site() {
		self::invalidate_site();
	}

	/**
	 * Invalidate a post. Builds a list of URLs to invalidate.
	 *
	 * @param WP_Post $post the post to invalidate.
	 *
	 * @return Aws\Result object on success or \WP_Error object on failure
	 */
	public static function invalidate_post( $post ) {
		if ( ! is_object( $post ) ) {
			return;
		}

		$permalink = get_permalink( $post );

		// Sanitize permalink for trashed post.
		if ( 'trash' === $post->post_status ) {
			$permalink = str_replace( '__trashed', '', $permalink );
		}

		// Build array list with post permalink and related urls.
		$urls = array_merge( array( $permalink ), self::get_related_urls( $post ) );

		// Make all URLs relative.
		$urls_to_invalidate = array_map( 'wp_make_link_relative', $urls );

		if ( in_array( '', $urls_to_invalidate, true ) ) {
			return self::invalidate_site();
		} else {
			return self::invalidate_urls( $urls_to_invalidate );
		}
	}

	/**
	 * Calculate the caller reference and invoke the invalidation.
	 *
	 * @param array  $urls the URLs to invalidate.
	 * @param string $caller_reference the caller reference.
	 *
	 * @return Aws\Result object on success or \WP_Error object on failure
	 */
	protected static function invalidate_urls( $urls, $caller_reference = '' ) {
		// Create the caller reference if not set.
		if ( ! $caller_reference ) {
			$caller_reference = self::get_caller_reference( $urls );
		}

		// Trigger the invalidation.
		return self::create_invalidation( $urls, $caller_reference );
	}

	/**
	 * Get related URLs for a post.
	 *
	 * This function builds an array of related URLs that must be invalidated
	 * along with the given post. Examples include taxonomies, author pages,
	 * archive pages, and the home page if the post would appear there.
	 *
	 * @param \WP_Post $post The post being invalidated.
	 *
	 * @return array
	 */
	protected static function get_related_urls( $post ) {
		$urls = array();

		// Logic derived from wp-cloudflare-page-cache
		// Taxonomies.
		$object_taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $object_taxonomies as $taxonomy ) {
			if ( is_object( $taxonomy ) && ( false === $taxonomy->public || false === $taxonomy->rewrite ) ) {
				continue;
			}

			$terms = get_the_terms( $post->ID, $taxonomy );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );

				if ( ! is_wp_error( $term_link ) ) {
					array_push( $urls, $term_link );
				}
			}
		}

		// Author pages.
		array_push(
			$urls,
			get_author_posts_url( get_post_field( 'post_author', $post->ID ) ),
			get_author_feed_link( get_post_field( 'post_author', $post->ID ) )
		);

		// Archive pages.
		if ( true === get_post_type_archive_link( $post->post_type ) ) {
			array_push(
				$urls,
				get_post_type_archive_link( $post->post_type ),
				get_post_type_archive_feed_link( $post->post_type )
			);
		}

		// Home page if it shows posts.
		$home_page_url = get_permalink( get_option( 'page_for_posts' ) );
		if ( is_string( $home_page_url ) && ! empty( $home_page_url ) && 'page' === get_option( 'show_on_front' ) && 'post' === $post->post_type ) {
			array_push( $urls, $home_page_url );
		}

		// Allow other plugins to modify.
		$urls = apply_filters( 'evaporation_related_urls', $urls, $post );

		return $urls;
	}

	/**
	 * Return the AWS Region.
	 *
	 * Return the AWS Region. Cloudfront is global but the
	 * AWS SDK requires a region. If the `AWS_DEFAULT_REGION`
	 * constant is set it will return that; if not it checks
	 * for an environment variable is set. Otherwise, returns `us-east-1`.
	 *
	 * @return string
	 */
	protected static function get_aws_region() {
		if ( defined( AWS_DEFAULT_REGION ) ) {
			return AWS_DEFAULT_REGION;
		}
		if ( getenv( 'AWS_DEFAULT_REGION' ) ) {
			return getenv( 'AWS_DEFAULT_REGION' );
		}
		return 'us-east-1';
	}

	/**
	 * Return the Cloudfront distribution id.
	 *
	 * Return the Cloudfront distribution id. If the
	 * `EVAPORATION_DISTRIBUTION_ID` constant is set it will
	 * return that; if not it checks for an environment
	 * variable is set. Otherwise, returns null.
	 *
	 * @return string|null
	 */
	protected static function get_distribution_id() {
		if ( EVAPORATION_DISTRIBUTION_ID ) {
			return EVAPORATION_DISTRIBUTION_ID;
		}
		if ( getenv( 'EVAPORATION_DISTRIBUTION_ID' ) ) {
			return getenv( 'EVAPORATION_DISTRIBUTION_ID' );
		}
		return null;
	}

	/**
	 * Generate a suitable caller reference for the invalidation request.
	 *
	 * @param array $urls The URLs to invalidate.
	 *
	 * @return string
	 */
	protected static function get_caller_reference( $urls = array() ) {
		if ( ! $urls ) {
			return time();
		} else {
			return time() . '_' . hash( 'sha256', wp_json_encode( $urls ) );
		}
	}

	/**
	 * Create a full site invalidation.
	 *
	 * @param string $caller_reference Caller reference for the invalidation.
	 *
	 * @return Aws\Result object on success or \WP_Error object on failure
	 */
	public static function invalidate_site( $caller_reference = '' ) {
		// Create the caller reference if not set.
		if ( ! $caller_reference ) {
			$caller_reference = self::get_caller_reference();
		}

		// Trigger the invalidation.
		return self::create_invalidation( array( '/*' ), $caller_reference );
	}

	/**
	 * Creates an invalidation for the given paths.
	 *
	 * @param array  $items all the URLs to invalidate in the batch.
	 * @param string $caller_reference the calculated caller reference.
	 *
	 * @return Aws\Result object on success or \WP_Error object on failure
	 */
	protected static function create_invalidation( $items, $caller_reference ) {
		// Get the client.
		$cloudfront_client = new CloudFrontClient(
			array(
				'region'  => self::get_aws_region(),
				'version' => '2020-05-31',
			)
		);

		try {
			$result = $cloudfront_client->createInvalidation(
				array(
					'DistributionId'    => self::get_distribution_id(),
					'InvalidationBatch' => array(
						'CallerReference' => $caller_reference,
						'Paths'           => array(
							'Items'    => $items,
							'Quantity' => count( $items ),
						),
					),
				)
			);
			return $result;
		} catch ( CredentialsException | CloudFrontException | InvalidArgumentException $e ) {
			return new \WP_Error( 'evaporation_cloudfront_error', $e->getMessage() );
		}
	}
}
