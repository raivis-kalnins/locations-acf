<?php
get_header();

$archive_text = get_field('loc_archive_text', 'option');
if ($archive_text) {
    echo '<div class="loc-archive-intro">' . $archive_text . '</div>';
}

echo do_shortcode('[wp_paginated_lp]');

get_footer();
?>