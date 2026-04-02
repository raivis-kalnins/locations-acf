<?php
/*
Plugin Name: Locations ACF
Description: Complete locations plugin with ACF integration, CSV upload, shortcodes, Leaflet map, and meta replacement.
Version: 1.4.1
Author: Raivis Kalnins
Requires Plugins: advanced-custom-fields-pro
Requires at least: 6.0
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) exit;

// ACF check
if (!function_exists('acf_add_options_page') || !function_exists('acf_add_local_field_group')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Locations ACF:</strong> Advanced Custom Fields (ACF) plugin is required.</p></div>';
    });
    return;
}

/**
 * Build LP title from global settings
 */
function locations_acf_build_location_title($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    if (!$post_id || !function_exists('get_field')) {
        return $post_id ? get_the_title($post_id) : '';
    }

    $city       = trim((string) get_field('city', $post_id));
    $county     = trim((string) get_field('county', $post_id));
    $manual     = trim((string) get_field('loc_main_title', $post_id));
    $mode       = (string) get_field('loc_auto_title_mode', 'option');
    $title_code = (string) get_field('loc_title_format', 'option');

    if ($mode === '') {
        $mode = 'manual';
    }

    if ($title_code === '') {
        $title_code = '[city]';
    }

    if ($mode === 'manual') {
        return $manual !== '' ? $manual : ($city !== '' ? $city : get_the_title($post_id));
    }

    if ($mode === 'city') {
        return $city !== '' ? $city : ($manual !== '' ? $manual : get_the_title($post_id));
    }

    if ($mode === 'format') {
        $title = str_replace(
            ['[city]', '[county]', '[title]'],
            [$city, $county, get_the_title($post_id)],
            $title_code
        );

        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim((string) $title, " \t\n\r\0\x0B,-");

        return $title !== '' ? $title : ($city !== '' ? $city : ($manual !== '' ? $manual : get_the_title($post_id)));
    }

    return $manual !== '' ? $manual : ($city !== '' ? $city : get_the_title($post_id));
}

/**
 * Helper: get custom main title or fallback
 */
function locations_acf_get_main_title($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    if (!$post_id) {
        return '';
    }

    return locations_acf_build_location_title($post_id);
}

/**
 * Helper: get raw city only
 */
function locations_acf_get_city($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    if (!$post_id || !function_exists('get_field')) {
        return $post_id ? get_the_title($post_id) : '';
    }

    $city = trim((string) get_field('city', $post_id));

    return $city !== '' ? $city : get_the_title($post_id);
}

/**
 * Helper: get raw county only
 */
function locations_acf_get_county($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    if (!$post_id || !function_exists('get_field')) {
        return '';
    }

    return trim((string) get_field('county', $post_id));
}

/**
 * Register ACF Options and Field Groups
 */
add_action('acf/init', function() {

    // Options page
    acf_add_options_page([
        'page_title'  => 'Locations',
        'menu_title'  => 'Locations',
        'menu_slug'   => 'locations-settings',
        'parent_slug' => 'options-general.php',
        'capability'  => 'edit_posts',
        'redirect'    => false,
        'position'    => 2,
        'icon_url'    => 'dashicons-location',
    ]);

    // Options fields
    acf_add_local_field_group([
        'key' => 'group_locations_options',
        'title' => 'Locations Options',
        'fields' => [
            [
                'key' => 'field_loc_pages',
                'label' => 'Enable Locations Pages',
                'name' => 'loc_pages',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0
            ],
            [
                'key' => 'field_loc_random_text',
                'label' => 'Loc Random Text [loc_main_title] = H1/main title [loc_city] = real city only [loc_county] = county only',
                'name' => 'loc_random_text',
                'type' => 'wysiwyg'
            ],
            [
                'key' => 'field_loc_random_images',
                'label' => 'Loc Random Images',
                'name' => 'loc_random_images',
                'type' => 'gallery',
                'return_format' => 'url'
            ],
            [
                'key' => 'field_loc_meta_title',
                'label' => 'Loc Meta Title',
                'name' => 'loc_meta_title',
                'type' => 'text'
            ],
            [
                'key' => 'field_loc_meta_description',
                'label' => 'Loc Meta Description',
                'name' => 'loc_meta_description',
                'type' => 'text'
            ],
            [
                'key' => 'field_loc_keywords',
                'label' => 'Loc Keywords',
                'name' => 'loc_keywords',
                'type' => 'text'
            ],
            [
                'key' => 'field_loc_archive_text',
                'label' => 'Loc Archive Intro Text',
                'name' => 'loc_archive_text',
                'type' => 'wysiwyg'
            ],
            [
                'key' => 'field_loc_auto_title_mode',
                'label' => 'Auto Title Mode',
                'name' => 'loc_auto_title_mode',
                'type' => 'select',
                'choices' => [
                    'manual' => 'Manual ACF Main Title only',
                    'city'   => 'Use City as title',
                    'format' => 'Use title format code',
                ],
                'default_value' => 'manual',
                'ui' => 1,
                'instructions' => 'Choose how location titles are generated.'
            ],
            [
                'key' => 'field_loc_title_format',
                'label' => 'Location Title Code',
                'name' => 'loc_title_format',
                'type' => 'text',
                'default_value' => '[city]',
                'instructions' => 'Available tags: [city], [county], [title]. Example: Electricians in [city], [county]'
            ],
        ],
        'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => 'locations-settings']]]
    ]);

    // Single page title field
    acf_add_local_field_group([
        'key' => 'group_location_single',
        'title' => 'Location Page Settings',
        'fields' => [
            [
                'key' => 'field_loc_main_title',
                'label' => 'Main Title',
                'name' => 'loc_main_title',
                'type' => 'text',
                'instructions' => 'Custom H1 for this location page'
            ]
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'lp']]]
    ]);

    // Location meta fields
    acf_add_local_field_group([
        'key' => 'group_location_meta',
        'title' => 'Location Details',
        'fields' => [
            ['key' => 'field_city', 'label' => 'City', 'name' => 'city', 'type' => 'text'],
            ['key' => 'field_county', 'label' => 'County', 'name' => 'county', 'type' => 'text'],
            ['key' => 'field_lat', 'label' => 'Latitude', 'name' => 'lat', 'type' => 'text'],
            ['key' => 'field_lng', 'label' => 'Longitude', 'name' => 'lng', 'type' => 'text'],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'lp']]],
    ]);

});

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, function() {
    if (function_exists('update_field')) {
        update_field('loc_pages', false, 'option');
    }
    flush_rewrite_rules();
});

/**
 * Register CPT
 */
add_action('init', function() {
    if (!function_exists('get_field')) return;

    $loc_pages = get_field('loc_pages', 'option');
    if (!$loc_pages) return;

    register_post_type('lp', [
        'labels' => [
            'name' => 'Locations',
            'singular_name' => 'Location',
            'menu_name' => 'Locations',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Location',
            'edit_item' => 'Edit Location',
            'new_item' => 'New Location',
            'view_item' => 'View Location',
            'search_items' => 'Search Locations',
            'not_found' => 'No locations',
            'not_found_in_trash' => 'No locations in trash'
        ],
        'public' => true,
        'has_archive' => 'areas-we-cover',
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-location',
        'menu_position' => 25,
        'rewrite' => ['slug' => 'areas-we-cover', 'with_front' => false],
    ]);
}, 20);

/**
 * Sync ACF/generated title to real WP post title
 */
add_action('acf/save_post', 'locations_acf_sync_main_title_to_post_title', 20);

function locations_acf_sync_main_title_to_post_title($post_id) {
    if (!is_numeric($post_id)) return;

    $post_id = (int) $post_id;

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    if (get_post_type($post_id) !== 'lp') return;
    if (!function_exists('get_field')) return;

    $new_title = locations_acf_build_location_title($post_id);
    $new_title = trim((string) $new_title);

    if ($new_title === '') return;

    $current_post = get_post($post_id);
    if (!$current_post) return;
    if ($current_post->post_title === $new_title) return;

    remove_action('acf/save_post', 'locations_acf_sync_main_title_to_post_title', 20);

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => sanitize_text_field($new_title),
        'post_name'  => sanitize_title($new_title),
    ]);

    add_action('acf/save_post', 'locations_acf_sync_main_title_to_post_title', 20);
}

/**
 * Bulk regenerate titles from Locations settings
 */
add_action('admin_post_locations_acf_regenerate_titles', 'locations_acf_regenerate_titles_from_settings');

function locations_acf_regenerate_titles_from_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('locations_acf_regenerate_titles_action', 'locations_acf_regenerate_titles_nonce');

    $query = new WP_Query([
        'post_type'      => 'lp',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    if (!empty($query->posts)) {
        remove_action('acf/save_post', 'locations_acf_sync_main_title_to_post_title', 20);

        foreach ($query->posts as $post_id) {
            $new_title = trim((string) locations_acf_build_location_title($post_id));

            if ($new_title === '') {
                continue;
            }

            wp_update_post([
                'ID'         => $post_id,
                'post_title' => sanitize_text_field($new_title),
                'post_name'  => sanitize_title($new_title),
            ]);
        }

        add_action('acf/save_post', 'locations_acf_sync_main_title_to_post_title', 20);
    }

    $redirect = add_query_arg([
        'post_type' => 'lp',
        'page' => 'generate_locations',
        'titles_updated' => 1,
    ], admin_url('edit.php'));

    wp_safe_redirect($redirect);
    exit;
}

/**
 * Change admin title placeholder for lp
 */
add_filter('enter_title_here', function($text, $post) {
    if ($post && isset($post->post_type) && $post->post_type === 'lp') {
        return 'Main location title';
    }

    return $text;
}, 10, 2);

/**
 * Admin submenu for generating locations
 */
add_action('admin_menu', function () {
    if (!function_exists('get_field')) return;
    if (!get_field('loc_pages', 'option')) return;

    add_submenu_page(
        'edit.php?post_type=lp',
        'Generate Locations',
        'Generate Locations',
        'manage_options',
        'generate_locations',
        'render_generate_locations_form'
    );
});

/**
 * CSV / Textarea generator form
 */
function render_generate_locations_form() {
    if (isset($_GET['titles_updated']) && (int) $_GET['titles_updated'] === 1) {
        echo '<div class="notice notice-success"><p>Location titles updated from Locations settings.</p></div>';
    }
    ?>
    <div class="wrap" style="max-width:900px;margin:0 auto;padding:2rem;">
        <h1>Generate City Pages</h1>

        <div style="margin:0 0 24px;padding:16px;background:#fff;border:1px solid #ccd0d4;">
            <h2 style="margin-top:0;">Update Existing Location Titles</h2>
            <p>
                Current mode:
                <strong><?php echo esc_html((string) get_field('loc_auto_title_mode', 'option') ?: 'manual'); ?></strong><br>
                Current code:
                <code><?php echo esc_html((string) get_field('loc_title_format', 'option') ?: '[city]'); ?></code>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="locations_acf_regenerate_titles">
                <?php wp_nonce_field('locations_acf_regenerate_titles_action', 'locations_acf_regenerate_titles_nonce'); ?>
                <?php submit_button('Update All Existing Location Titles', 'secondary', 'submit', false); ?>
            </form>
        </div>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('generate_locations_nonce','generate_locations_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="locations">Locations CSV/Textarea</label></th>
                    <td>
                        <textarea name="locations" id="locations" rows="10" style="width:100%;"></textarea>
                        <p>One city per line: County|City|Lat|Lng</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="locations_csv">Or Upload CSV</label></th>
                    <td><input type="file" name="locations_csv" accept=".csv" /></td>
                </tr>
            </table>
            <?php submit_button('Generate Locations'); ?>
        </form>
    </div>
    <?php

    // Process submission
    if (isset($_POST['generate_locations_nonce']) && check_admin_referer('generate_locations_nonce','generate_locations_nonce')) {
        $lines = [];

        if (!empty($_POST['locations'])) {
            $lines = explode("\n", wp_unslash($_POST['locations']));
        }

        if (!empty($_FILES['locations_csv']['tmp_name'])) {
            if (($handle = fopen($_FILES['locations_csv']['tmp_name'], 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $lines[] = implode('|', $data);
                }
                fclose($handle);
            }
        }

        $created = 0;
        $skip_first = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            if ($skip_first) {
                $skip_first = false;
                continue;
            }

            $data = str_getcsv($line, '|');
            list($county, $city, $lat, $lng) = array_pad(array_map('trim', $data), 4, '');

            if (!empty($city) && !get_page_by_title($city, OBJECT, 'lp')) {
                $post_id = wp_insert_post([
                    'post_title'  => $city,
                    'post_type'   => 'lp',
                    'post_status' => 'publish',
                    'post_name'   => sanitize_title($city)
                ]);

                if ($post_id && !is_wp_error($post_id)) {
                    update_field('city', $city, $post_id);
                    update_field('county', $county, $post_id);
                    update_field('lat', $lat, $post_id);
                    update_field('lng', $lng, $post_id);

                    $generated_title = locations_acf_build_location_title($post_id);

                    if ($generated_title !== '') {
                        update_field('loc_main_title', $generated_title, $post_id);

                        wp_update_post([
                            'ID'         => $post_id,
                            'post_title' => sanitize_text_field($generated_title),
                            'post_name'  => sanitize_title($generated_title),
                        ]);
                    }

                    $created++;
                }
            }
        }

        if ($created > 0) {
            echo '<div class="notice notice-success"><p>Created ' . esc_html($created) . ' city pages.</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>No new locations created.</p></div>';
        }
    }
}

/**
 * Shortcodes including Google Map
 */
add_action('init', function() {
    if (!function_exists('get_field')) return;
    if (!get_field('loc_pages','option')) return;

    // City only. Do not use main title here.
    add_shortcode('loc_city', function($atts) {
        $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'loc_city');
        return '<span class="loc-city">' . esc_html(locations_acf_get_city($atts['id'])) . '</span>';
    });

    add_shortcode('loc_county', function($atts) {
        $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'loc_county');
        return '<span class="loc-county">' . esc_html(locations_acf_get_county($atts['id'])) . '</span>';
    });

    add_shortcode('loc_main_title', function($atts) {
        $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'loc_main_title');
        return '<span class="loc-main-title">' . esc_html(locations_acf_get_main_title($atts['id'])) . '</span>';
    });

    add_shortcode('lp_google_map', function($atts) {
        $atts = shortcode_atts([
            'id'     => get_the_ID(),
            'width'  => '100%',
            'height' => '400px',
            'zoom'   => 14
        ], $atts, 'lp_google_map');

        $post_id = (int) $atts['id'];
        $lat = get_field('lat', $post_id);
        $lng = get_field('lng', $post_id);

        if (!$lat || !$lng) {
            return '<p>No map coordinates available.</p>';
        }

        $iframe_src = esc_url("https://maps.google.com/maps?q={$lat},{$lng}&z={$atts['zoom']}&output=embed");

        ob_start();
        ?>
        <div class="lp-google-map-wrapper">
            <iframe width="<?php echo esc_attr($atts['width']); ?>" height="<?php echo esc_attr($atts['height']); ?>" frameborder="0" style="border:0;width:100%;height:<?php echo esc_attr($atts['height']); ?>;" src="<?php echo $iframe_src; ?>" allowfullscreen loading="lazy"></iframe>
        </div>
        <?php
        return ob_get_clean();
    });
});

/**
 * Homepage expandable left location icon (white, expandable)
 */
add_action('wp_footer', function() {
    if (!function_exists('get_field')) return;
    if (!get_field('loc_pages', 'option')) return;

    if (!(is_front_page() || is_home())) return;

    $query = new WP_Query([
        'post_type'      => 'lp',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'rand',
        'no_found_rows'  => true,
    ]);

    if (!$query->have_posts()) return;
    ?>
    <style>
    #random-locations-box{
        position:fixed;
        bottom:100px;
        left:0;
        z-index:99999;
        display:flex;
        align-items:flex-start;
    }
    #random-locations-handle{
        width:50px;
        height:50px;
        background:#333;
        border-radius:0 25px 25px 0;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        flex:0 0 50px;
    }
    #random-locations-handle svg{
        width:22px;
        height:22px;
        fill:#fff !important;
    }
    #random-locations-list{
        width:0;
        overflow:hidden;
        background:#fff;
        transition:width 0.3s ease, padding 0.3s ease;
        box-shadow:2px 2px 15px rgba(0,0,0,0.2);
        padding:0;
        white-space:nowrap;
    }
    #random-locations-box.open #random-locations-list{
        width:220px;
        padding:10px;
    }
    #random-locations-list ul{
        list-style:none;
        margin:0;
        padding:0;
    }
    #random-locations-list li{
        margin-bottom:6px;
    }
    #random-locations-list li:last-child{
        margin-bottom:0;
    }
    #random-locations-list a{
        font-size:13px;
        color:#333;
        text-decoration:none;
    }
    #random-locations-list a:hover{
        text-decoration:underline;
    }
    </style>

    <div id="random-locations-box" aria-label="Random locations">
        <div id="random-locations-handle" role="button" tabindex="0" aria-expanded="false" aria-controls="random-locations-list">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7zM12 11.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>
            </svg>
        </div>
        <div id="random-locations-list">
            <ul>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <li>
                        <a href="<?php the_permalink(); ?>">
                            <?php echo esc_html(locations_acf_get_city(get_the_ID())); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var box = document.getElementById('random-locations-box');
        var handle = document.getElementById('random-locations-handle');

        if (!box || !handle) return;

        function toggleLocationsBox() {
            box.classList.toggle('open');
            handle.setAttribute('aria-expanded', box.classList.contains('open') ? 'true' : 'false');
        }

        handle.addEventListener('click', toggleLocationsBox);

        handle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleLocationsBox();
            }
        });
    });
    </script>
    <?php
    wp_reset_postdata();
});

/**
 * Template override
 */
add_filter('template_include', function($template) {
    if (!function_exists('get_field')) return $template;
    if (!get_field('loc_pages','option')) return $template;

    if (is_singular('lp')) {
        $t = plugin_dir_path(__FILE__) . 'templates/single-lp.php';
        if (file_exists($t)) return $t;
    }

    if (is_post_type_archive('lp')) {
        $t = plugin_dir_path(__FILE__) . 'templates/archive-lp.php';
        if (file_exists($t)) return $t;
    }

    return $template;
});