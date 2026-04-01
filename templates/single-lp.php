<?php
    get_header();
    $home_url = get_home_url();
    header('Location: '.$home_url.'/lp/');
        echo do_shortcode( '[wp_single_lp]' );
    die();
    get_footer();