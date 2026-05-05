<?php
$locations_acf_is_block_theme = function_exists('wp_is_block_theme') && wp_is_block_theme();
$locations_acf_block_header = '';
$locations_acf_block_footer = '';

if ($locations_acf_is_block_theme) {
    // Render block template parts before wp_head/wp_footer so Gutenberg, WooCommerce
    // and Interactivity API script modules can register their import map on time.
    $locations_acf_block_header = do_blocks('<!-- wp:template-part {"slug":"header","tagName":"header"} /-->');
    $locations_acf_block_footer = do_blocks('<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->');
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<?php echo $locations_acf_block_header; ?>
<main class="wp-block-group">
<?php
} else {
    get_header();
}

if ($locations_acf_is_block_theme) {
    echo '<div style="height:230px" aria-hidden="true" class="wp-block-spacer"></div>';
}

$post_id = get_the_ID();
$main_title = get_field('loc_main_title', $post_id) ?: get_the_title($post_id);
$random_text = locations_acf_render_dynamic_text((string) locations_acf_get_effective_field('loc_random_text', $post_id, ''), $post_id);
$random_images = locations_acf_get_effective_field('loc_random_images', $post_id, []);
?>
<style>
.lp-container { max-width: 1320px; margin: 0 auto; background: #fff; padding: 70px 30px; box-sizing: border-box; }
.lp-container h1 { font-size: 34px; margin-bottom: 30px; }
.lp-media-text { display: grid; grid-template-columns: minmax(280px, 40%) minmax(0, 60%); gap: 30px; align-items: start; margin-bottom: 30px; }
.lp-media-text img { display: block; width: 100%; max-width: 100%; height: auto; min-height: 260px; object-fit: cover; border-radius: 8px; }
.lp-media-text .lp-text { min-width: 0; padding: 0; }
.lp-media-text .lp-text > :first-child { margin-top: 0; }
.lp-media-text .entry-content { max-width: 100%; }
@media(max-width:900px){ .lp-media-text { grid-template-columns: 1fr; gap: 20px; } .lp-media-text img { min-height: 0; } }
</style>

<div class="lp-container">
    <h1><?php echo esc_html(locations_acf_get_main_title()); ?></h1>

    <?php if (!empty($random_images)) :
        $random_img = $random_images[array_rand($random_images)];
    ?>
        <div class="lp-media-text">
            <img src="<?php echo esc_url($random_img); ?>" alt="<?php echo esc_attr($main_title); ?>">
            <div class="lp-text">
                <?php if ($random_text) echo wp_kses_post($random_text); ?>
                <div class="entry-content"><?php the_content(); ?></div>
            </div>
        </div>
    <?php else : ?>
        <div class="entry-content"><?php the_content(); ?></div>
    <?php endif; ?>

    <?php echo do_shortcode('[lp_google_map]'); ?>
</div>

<?php if ($locations_acf_is_block_theme) : ?>
</main>
<?php echo $locations_acf_block_footer; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php else : ?>
<?php get_footer(); ?>
<?php endif; ?>
