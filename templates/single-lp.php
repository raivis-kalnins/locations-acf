<?php
get_header();

if (have_posts()) : while (have_posts()) : the_post(); ?>

<h1><?php the_title(); ?></h1>

<?php
// Google map shortcode for this post
echo do_shortcode('[lp_google_map]');

// Post-specific fields
$city   = get_field('city');
$county = get_field('county');
if ($city || $county) {
    echo '<p>';
    if ($city) echo 'City: ' . esc_html($city) . '<br>';
    if ($county) echo 'County: ' . esc_html($county);
    echo '</p>';
}

// Optional random text/images from plugin options
$random_text   = get_field('loc_random_text', 'option');
$random_images = get_field('loc_random_images', 'option');
if ($random_text) echo '<div class="loc-random-text">' . $random_text . '</div>';
if ($random_images):
    echo '<div class="loc-random-images">';
    foreach ($random_images as $img) echo '<img src="'. esc_url($img) .'" style="max-width:200px;margin:5px;">';
    echo '</div>';
endif;
?>

<div class="entry-content"><?php the_content(); ?></div>

<?php endwhile; endif;
get_footer();