<?php

/**
 * Invalidate content in Cloudfront.
 *
 * @package wp-cli
 */
class Invalidate_Command extends \WP_CLI_Command {
    /**
     * Invalidate a post.
     *
     * ## OPTIONS
     *
     * <id>
     * : The id of the post
     */
    public function post( $args, $assoc_args ) {
        list( $post_id ) = $args;

        $post = get_post( $post_id );
        $result = \Evaporation\Base::invalidate_post( $post );
        if ( is_object( $result ) && 'Aws\Result' === get_class( $result ) ) {
            foreach( $result["Invalidation"]["InvalidationBatch"]["Paths"]["Items"] as $item ) {
                WP_CLI::log( "Invalidation created for $item" );
            }
            WP_CLI::success( "Invalidation ID " . $result["Invalidation"]["Id"] . " created" );
        } else {
            WP_CLI::error( "Invalidation creation failed: $result" );
        }
    }
}

WP_CLI::add_command( 'evaporation invalidate', 'Invalidate_Command' );