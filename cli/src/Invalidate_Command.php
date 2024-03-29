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
                \Evaporation\Base::invalidate_post( $post );
        }
}

WP_CLI::add_command( 'evaporation invalidate', 'Invalidate_Command' );