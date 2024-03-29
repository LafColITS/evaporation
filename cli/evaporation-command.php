<?php

if ( ! class_exists( 'WP_CLI' ) ) {
        return;
}

if ( ! class_exists( 'Invalidate_Command') ) {
        require 'src/Invalidate_Command.php';
}