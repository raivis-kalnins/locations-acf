<?php
get_header();
$post_id = get_the_ID();
// ACF Fields
$main_title = get_field('loc_main_title', $post_id) ?: get_the_title($post_id);
$random_text = get_field('loc_random_text', 'option');
$random_images = get_field('loc_random_images', 'option');
?>
<style>
.lp-container {
    max-width: 1320px;
    margin: 0 auto;
    background: #fff;
    padding: 70px 30px;
}
.lp-media-text {
    display: flex;
    gap: 0;
    margin-bottom: 30px;
}
.lp-media-text img {
    flex: 0 0 40%;
    max-width: 40%;
    object-fit: cover;
    border-radius: 8px;
}
.lp-media-text .lp-text {
    flex: 0 0 60%;
    padding-left: 40px;
}
@media(max-width:900px){
    .lp-media-text { flex-direction: column; }
    .lp-media-text img,
    .lp-media-text .lp-text { flex: 0 0 100%; max-width:100%; order: -1; }
}
</style>
<div class="lp-container">
    <h1><?php echo esc_html($main_title); ?></h1>
    <?php if(!empty($random_images)):
        $random_img = $random_images[array_rand($random_images)];
    ?>
    <div class="lp-media-text">
        <img src="<?php echo esc_url($random_img); ?>" alt="<?php echo esc_attr($main_title); ?>">
        <div class="lp-text">
            <?php if($random_text) echo '<p>'.$random_text.'</p>'; ?>
            <div class="entry-content"><?php the_content(); ?></div>
        </div>
    </div>
    <?php else: ?>
        <div class="entry-content"><?php the_content(); ?></div>
    <?php endif; ?>
    <?php echo do_shortcode('[lp_google_map]'); ?>
</div>
<?php get_footer(); ?>