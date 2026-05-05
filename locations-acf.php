<?php
/*
Plugin Name: Locations ACF
Description: Complete locations plugin with ACF integration, CSV upload, shortcodes, Leaflet map, and meta replacement.
Version: 1.8.1
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
 * Helper: return per-location override field when enabled, otherwise global option value
 */
function locations_acf_get_effective_field($field_name, $post_id = 0, $default = null) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    if (!function_exists('get_field')) {
        return $default;
    }

    if ($post_id && get_post_type($post_id) === 'lp') {
        $use_individual = (bool) get_field('loc_use_individual_settings', $post_id);

        if ($use_individual) {
            $value = get_field($field_name, $post_id);

            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }
    }

    $option_value = get_field($field_name, 'option');

    if ($option_value !== null && $option_value !== '' && $option_value !== []) {
        return $option_value;
    }

    return $default;
}

/**
 * Replace location placeholders in dynamic text
 */
function locations_acf_render_dynamic_text($content, $post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    if (!is_string($content) || $content === '') {
        return '';
    }

    $replacements = [
        '[loc_main_title]' => locations_acf_get_main_title($post_id),
        '[loc_city]'       => locations_acf_get_city($post_id),
        '[loc_county]'     => locations_acf_get_county($post_id),
    ];

    return strtr($content, $replacements);
}

/**
 * Build effective SEO meta for a location page
 */
function locations_acf_get_effective_meta($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();

    $title = trim((string) locations_acf_get_effective_field('loc_meta_title', $post_id, ''));
    $description = trim((string) locations_acf_get_effective_field('loc_meta_description', $post_id, ''));
    $keywords = trim((string) locations_acf_get_effective_field('loc_keywords', $post_id, ''));

    $replace = [
        '[loc_main_title]' => locations_acf_get_main_title($post_id),
        '[loc_city]'       => locations_acf_get_city($post_id),
        '[loc_county]'     => locations_acf_get_county($post_id),
    ];

    return [
        'title' => strtr($title, $replace),
        'description' => strtr($description, $replace),
        'keywords' => strtr($keywords, $replace),
    ];
}


/**
 * Shared admin tabs for Locations screens.
 */
function locations_acf_admin_page_url($page, $args = []) {
    $url = admin_url($page);
    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }
    return $url;
}

function locations_acf_get_admin_tabs() {
    $tabs = [
        'settings' => [
            'label' => 'Settings',
            'url'   => locations_acf_admin_page_url('options-general.php', ['page' => 'locations-settings']),
        ],
        'locations' => [
            'label' => 'All Locations',
            'url'   => locations_acf_admin_page_url('edit.php', ['post_type' => 'lp']),
        ],
        'add_new' => [
            'label' => 'Add New',
            'url'   => locations_acf_admin_page_url('post-new.php', ['post_type' => 'lp']),
        ],
        'generate' => [
            'label' => 'Generate Locations',
            'url'   => locations_acf_admin_page_url('admin.php', ['page' => 'generate_locations']),
        ],
    ];

    return $tabs;
}

function locations_acf_render_admin_tabs($active = 'settings') {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $tabs = locations_acf_get_admin_tabs();
    echo '<nav class="nav-tab-wrapper locations-acf-tabs" style="margin:16px 0 20px;">';
    foreach ($tabs as $key => $tab) {
        $class = 'nav-tab';
        if ($key === $active) {
            $class .= ' nav-tab-active';
        }
        echo '<a class="' . esc_attr($class) . '" href="' . esc_url($tab['url']) . '">' . esc_html($tab['label']) . '</a>';
    }
    echo '</nav>';
}

function locations_acf_current_admin_tab() {
    global $pagenow;

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';

    if ($page === 'generate_locations') {
        return 'generate';
    }

    if ($page === 'locations-settings') {
        return 'settings';
    }

    if ($post_type === 'lp' && $pagenow === 'post-new.php') {
        return 'add_new';
    }

    if ($post_type === 'lp' && $pagenow === 'edit.php') {
        return 'locations';
    }

    if ($pagenow === 'post.php') {
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        if ($post_id && get_post_type($post_id) === 'lp') {
            return 'locations';
        }
    }

    return '';
}

function locations_acf_maybe_render_admin_tabs() {
    $active = locations_acf_current_admin_tab();
    if ($active === '') {
        return;
    }

    locations_acf_render_admin_tabs($active);
}
add_action('all_admin_notices', 'locations_acf_maybe_render_admin_tabs');

/**
 * Register ACF Options and Field Groups
 */
add_action('acf/init', function() {

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
                'key' => 'field_loc_template_mode',
                'label' => 'Theme Template Mode',
                'name' => 'loc_template_mode',
                'type' => 'select',
                'choices' => [
                    'auto' => 'Auto detect theme type',
                    'classic' => 'Old / Classic theme',
                    'block' => 'Gutenberg / Block theme',
                ],
                'default_value' => 'auto',
                'ui' => 1,
                'instructions' => 'Use Auto for most sites. Choose Classic for older themes using header.php/footer.php. Choose Gutenberg for block themes using template parts.'
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
            ],
            [
                'key' => 'field_loc_use_individual_settings',
                'label' => 'Use Individual Page Settings',
                'name' => 'loc_use_individual_settings',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
                'instructions' => 'Enable this to override the global Locations options for this location only.'
            ],
            [
                'key' => 'field_loc_random_text_single',
                'label' => 'Loc Random Text Override',
                'name' => 'loc_random_text',
                'type' => 'wysiwyg',
                'conditional_logic' => [[['field' => 'field_loc_use_individual_settings', 'operator' => '==', 'value' => '1']]],
                'instructions' => 'Shown only for this location when individual page settings are enabled.'
            ],
            [
                'key' => 'field_loc_random_images_single',
                'label' => 'Loc Random Images Override',
                'name' => 'loc_random_images',
                'type' => 'gallery',
                'return_format' => 'url',
                'conditional_logic' => [[['field' => 'field_loc_use_individual_settings', 'operator' => '==', 'value' => '1']]],
                'instructions' => 'Used only for this location when individual page settings are enabled.'
            ],
            [
                'key' => 'field_loc_meta_title_single',
                'label' => 'Loc Meta Title Override',
                'name' => 'loc_meta_title',
                'type' => 'text',
                'conditional_logic' => [[['field' => 'field_loc_use_individual_settings', 'operator' => '==', 'value' => '1']]]
            ],
            [
                'key' => 'field_loc_meta_description_single',
                'label' => 'Loc Meta Description Override',
                'name' => 'loc_meta_description',
                'type' => 'text',
                'conditional_logic' => [[['field' => 'field_loc_use_individual_settings', 'operator' => '==', 'value' => '1']]]
            ],
            [
                'key' => 'field_loc_keywords_single',
                'label' => 'Loc Keywords Override',
                'name' => 'loc_keywords',
                'type' => 'text',
                'conditional_logic' => [[['field' => 'field_loc_use_individual_settings', 'operator' => '==', 'value' => '1']]]
            ]
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'lp']]]
    ]);

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
        update_field('loc_template_mode', 'auto', 'option');
    }
    flush_rewrite_rules();
});

/**
 * Flush rewrite rules when plugin settings change
 */
add_action('acf/save_post', function($post_id) {
    if ($post_id === 'options') {
        flush_rewrite_rules();
    }
}, 30);


/**
 * Resolve theme template mode for location templates.
 */
function locations_acf_get_template_mode() {
    $mode = function_exists('get_field') ? (string) get_field('loc_template_mode', 'option') : 'auto';

    if (!in_array($mode, ['auto', 'classic', 'block'], true)) {
        $mode = 'auto';
    }

    return $mode;
}

function locations_acf_is_block_template_mode() {
    $mode = locations_acf_get_template_mode();

    if ($mode === 'block') {
        return true;
    }

    if ($mode === 'classic') {
        return false;
    }

    return function_exists('wp_is_block_theme') && wp_is_block_theme();
}

function locations_acf_get_template_path($template_name) {
    $base = plugin_dir_path(__FILE__) . 'templates/';
    $variant = locations_acf_is_block_template_mode() ? 'block' : 'classic';
    $path = $base . $variant . '/' . $template_name;

    if (file_exists($path)) {
        return $path;
    }

    $fallback = $base . $template_name;
    return file_exists($fallback) ? $fallback : '';
}

function locations_acf_render_theme_block_part($slug, $fallback = '') {
    $slug = sanitize_key((string) $slug);

    if ($slug === '') {
        return false;
    }

    $candidate_paths = [
        trailingslashit(get_stylesheet_directory()) . 'parts/' . $slug . '.html',
        trailingslashit(get_template_directory()) . 'parts/' . $slug . '.html',
    ];

    foreach ($candidate_paths as $path) {
        if (!file_exists($path) || !is_readable($path)) {
            continue;
        }

        $content = file_get_contents($path);

        if (!is_string($content) || trim($content) === '') {
            continue;
        }

        echo do_blocks($content);
        return true;
    }

    if ($fallback === 'header') {
        get_header();
        return true;
    }

    if ($fallback === 'footer') {
        get_footer();
        return true;
    }

    return false;
}

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
        'show_in_menu' => false,
        'rewrite' => ['slug' => 'areas-we-cover', 'with_front' => false],
        'show_in_rest' => true,
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
        'page' => 'generate_locations',
        'titles_updated' => 1,
    ], admin_url('admin.php'));

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
        null,
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
 * Enqueue archive assets only on lp archive pages
 */
function locations_acf_enqueue_archive_assets() {
    if (!post_type_exists('lp')) {
        return;
    }

    if (!is_post_type_archive('lp')) {
        return;
    }

    wp_enqueue_style(
        'leaflet',
        'https://unpkg.com/leaflet/dist/leaflet.css',
        [],
        null
    );

    wp_enqueue_style(
        'leaflet-markercluster',
        'https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css',
        ['leaflet'],
        null
    );

    wp_enqueue_style(
        'leaflet-markercluster-default',
        'https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css',
        ['leaflet-markercluster'],
        null
    );

    wp_enqueue_script(
        'leaflet',
        'https://unpkg.com/leaflet/dist/leaflet.js',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'leaflet-markercluster',
        'https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js',
        ['leaflet'],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'locations_acf_enqueue_archive_assets');

/**
 * Shortcodes including Google Map
 */
add_action('init', function() {
    if (!function_exists('get_field')) return;
    if (!get_field('loc_pages','option')) return;

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

    add_shortcode('locations_archive', function () {
        $loc_archive_text = get_field('loc_archive_text', 'option') ?: '';

        $query = new WP_Query([
            'post_type' => 'lp',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $locations = [];

        while ($query->have_posts()) : $query->the_post();
            $lat = get_field('lat');
            $lng = get_field('lng');
            $city = function_exists('locations_acf_get_city') ? locations_acf_get_city(get_the_ID()) : get_the_title();
            $county = function_exists('locations_acf_get_county') ? locations_acf_get_county(get_the_ID()) : '';
            $url = get_permalink();

            $locations[] = [
                'id' => get_the_ID(),
                'lat' => $lat,
                'lng' => $lng,
                'city' => $city,
                'county' => $county,
                'url' => $url,
                'slug' => sanitize_title($city),
            ];
        endwhile;
        wp_reset_postdata();

        ob_start();
        ?>
        <style>
        .loc-archive-wrapper {
            position: relative;
            max-width: 1320px;
            margin: -20px auto 0 auto;
            background: #fff;
            padding: 70px 30px;
            box-sizing: border-box;
            overflow: hidden;
            z-index: 0;
        }
        .loc-archive-title { font-size: 32px; font-weight: 700; margin-bottom: 20px; color: #222; }
        .loc-archive-intro { font-size: 18px; color: #555; margin-bottom: 40px; }
        .loc-buttons-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            grid-auto-rows: 1fr;
            gap: 10px;
            width: 100%;
            margin-bottom: 26px;
            padding: 0;
        }
        .loc-button {
            width: 100%;
            max-width: none;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 12px;
            background-color: #333;
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            text-align: center;
            line-height: 1.2;
            word-break: break-word;
            box-sizing: border-box;
            border: 0;
            box-shadow: 0 0 0 0 rgba(232, 15, 15, 0);
        }
        .loc-button span {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }
        .loc-button:hover,
        .loc-button:focus,
        .loc-button.is-active {
            background-color: #e80f0f;
            color: #fff;
            outline: none;
        }
        .loc-button:hover {
            transform: translateY(-1px);
        }
        .loc-button.is-active {
            box-shadow: 0 0 0 3px rgba(232, 15, 15, 0.18);
            animation: locButtonPulse 1.6s ease-out;
        }
        @keyframes locButtonPulse {
            0% { box-shadow: 0 0 0 0 rgba(232, 15, 15, 0.35); }
            100% { box-shadow: 0 0 0 10px rgba(232, 15, 15, 0); }
        }
        #lp-map {
            width: 100%;
            height: 600px;
            margin-bottom: 30px;
        }
        .leaflet-popup-content-wrapper { border-radius: 8px; }
        .city-popup-link {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 12px;
            background: #333;
            color: #fff !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
        }
        .city-popup-link:hover { background: #555; }
        .marker-cluster div {
            background: #e80f0f !important;
            border-radius: 50% !important;
            border: 1px solid #fff !important;
            color: #fff !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 38px;
            min-height: 38px;
            font-weight: bold;
            font-size: 14px;
        }
        .marker-cluster span { color: #fff !important; font-weight: bold; text-align: center; }
        .loc-search-panel {
            margin-bottom: 22px;
        }
        .loc-search-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            max-width: 980px;
            margin: 0 auto 0 auto;
        }
        .loc-search-field {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 10px 14px;
            border: 1px solid #d0d0d0;
            border-radius: 999px;
            background: #fff;
        }
        .loc-search-label {
            flex: 0 0 auto;
            font-size: 14px;
            font-weight: 600;
            color: #222;
            white-space: nowrap;
            margin: 0;
        }
        .loc-search-input {
            width: 100%;
            padding: 0;
            border: 0;
            border-radius: 0;
            font-size: 15px;
            box-sizing: border-box;
            background: transparent;
        }
        .loc-search-input:focus { outline: none; }
        .loc-search-status {
            margin-top: 0;
            color: #666;
            font-size: 14px;
            text-align: right;
            white-space: nowrap;
        }
        .loc-search-results {
            margin-top: 14px;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .loc-search-result {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 14px;
            background: #f6f6f6;
            border-radius: 8px;
            text-decoration: none;
            color: #222;
            font-weight: 600;
            text-align: center;
            line-height: 1.3;
        }
        .loc-search-result:hover,
        .loc-search-result:focus {
            background: #ececec;
            color: #222;
            outline: none;
        }
        @media(max-width:1024px){
            .loc-buttons-wrapper {
                grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
                gap: 9px;
            }
            .loc-button {
                min-height: 40px;
                padding: 8px 10px;
                font-size: 14px;
            }
        }
        @media(max-width:768px){
            .loc-buttons-wrapper {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 7px;
            }
            .loc-button {
                min-height: 36px;
                max-height: 42px;
                padding: 6px 8px;
                border-radius: 6px;
                font-size: 13px;
                line-height: 1.12;
            }
            #lp-map { height: 400px; }
            .loc-search-wrap {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .loc-search-status {
                text-align: left;
                white-space: normal;
            }
            .loc-search-results { grid-template-columns: 1fr; }
        }
        @media(max-width:480px){
            .loc-archive-wrapper {
                padding-left: 16px;
                padding-right: 16px;
            }
            .loc-buttons-wrapper {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
            }
            .loc-button {
                min-height: 34px;
                max-height: 40px;
                padding: 5px 7px;
                font-size: 12px;
            }
        }
        </style>

        <div class="loc-archive-wrapper">
            <h1 class="loc-archive-title">Areas We Cover</h1>
            <?php if ($loc_archive_text): ?>
                <div class="loc-archive-intro"><?php echo wp_kses_post($loc_archive_text); ?></div>
            <?php endif; ?>

            <div class="loc-search-panel">
                <div class="loc-search-wrap">
                    <div class="loc-search-field">
                        <label class="loc-search-label" for="loc-ajax-search">Search</label>
                        <input type="search" id="loc-ajax-search" class="loc-search-input" placeholder="Type a city or county" autocomplete="off">
                    </div>
                    <div id="loc-search-status" class="loc-search-status"></div>
                </div>
                <div id="loc-search-results" class="loc-search-results"></div>
            </div>

            <div id="lp-map"></div>

            <div class="loc-buttons-wrapper">
                <?php foreach($locations as $loc): ?>
                    <button
                        type="button"
                        class="loc-button"
                        data-location-id="<?php echo esc_attr($loc['id']); ?>"
                        data-location-city="<?php echo esc_attr($loc['city']); ?>"
                        data-location-slug="<?php echo esc_attr($loc['slug']); ?>"
                        data-location-url="<?php echo esc_url($loc['url']); ?>"
                        aria-label="Show <?php echo esc_attr($loc['city']); ?> on the map"
                    >
                        <span><?php echo esc_html($loc['city']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof L === 'undefined') {
                return;
            }

            var mapEl = document.getElementById('lp-map');
            if (!mapEl) {
                return;
            }

            if (mapEl._leaflet_id) {
                return;
            }

            var map = L.map('lp-map', {zoomAnimation:true});
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var markers = L.markerClusterGroup({
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: true,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 40,
                chunkedLoading: true,
                chunkProgress: null,
                removeOutsideVisibleBounds: true,
                disableClusteringAtZoom: 12
            });

            var bounds = [];
            var locations = <?php echo wp_json_encode($locations); ?>;
            var markerRecords = [];
            var markerIndexById = {};
            var suppressNextHistoryUpdate = false;

            function createRedIcon(size){
                var newSize = size*0.75;
                return L.icon({
                    iconUrl:'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="'+newSize+'" height="'+(newSize*1.64)+'" viewBox="0 0 25 41"><path fill="%23e80f0f" d="M12.5 0C5.6 0 0 5.6 0 12.5c0 10.4 12.5 28.5 12.5 28.5S25 22.9 25 12.5C25 5.6 19.4 0 12.5 0z"/><circle cx="12.5" cy="12.5" r="5.5" fill="white"/></svg>',
                    iconSize:[newSize,newSize*1.64],
                    iconAnchor:[newSize/2,newSize*1.64],
                    popupAnchor:[0,-newSize*0.8],
                    shadowUrl:'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    shadowSize:[41,41]
                });
            }

            function normalizeLocationValue(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function getLocationStateUrl(record) {
                var url = new URL(window.location.href);
                url.searchParams.set('location', String(record.loc.id));
                url.searchParams.set('city', record.loc.city);
                return url.toString();
            }

            function updateLocationState(record, mode) {
                if (!record || !record.loc || suppressNextHistoryUpdate) {
                    return;
                }

                var nextUrl = getLocationStateUrl(record);

                if (mode === 'replace') {
                    window.history.replaceState({ locationId: record.loc.id }, '', nextUrl);
                } else {
                    window.history.pushState({ locationId: record.loc.id }, '', nextUrl);
                }
            }

            function rebuildMarkerLayer(items, shouldFitBounds) {
                markers.clearLayers();
                var activeBounds = [];

                items.forEach(function(record) {
                    if (record.marker) {
                        markers.addLayer(record.marker);
                        activeBounds.push([record.loc.lat, record.loc.lng]);
                    }
                });

                if (!map.hasLayer(markers)) {
                    map.addLayer(markers);
                }

                if (shouldFitBounds !== false) {
                    if (activeBounds.length) {
                        map.fitBounds(activeBounds, {padding:[20,20], maxZoom:18});
                    } else if (bounds.length) {
                        map.fitBounds(bounds, {padding:[20,20], maxZoom:18});
                    }
                }
            }

            locations.forEach(function(loc){
                if (!loc.lat || !loc.lng) {
                    return;
                }

                var marker = L.marker([loc.lat, loc.lng], {icon: createRedIcon(25)});
                marker.bindPopup('<strong>'+loc.city+'</strong><br><a href="'+loc.url+'" class="city-popup-link">View location page</a>');
                var record = { loc: loc, marker: marker };
                markerRecords.push(record);
                markerIndexById[String(loc.id)] = record;
                bounds.push([loc.lat, loc.lng]);
            });

            rebuildMarkerLayer(markerRecords, true);

            function setActiveButton(locationId, shouldScroll) {
                var activeButton = null;
                document.querySelectorAll('.loc-button').forEach(function(button) {
                    var isActive = String(button.getAttribute('data-location-id')) === String(locationId);
                    button.classList.toggle('is-active', isActive);
                    if (isActive) {
                        activeButton = button;
                    }
                });

                if (activeButton && shouldScroll && typeof activeButton.scrollIntoView === 'function') {
                    activeButton.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                }
            }

            function focusLocationOnMap(locationId, historyMode) {
                var record = markerIndexById[String(locationId)];

                if (!record || !record.marker) {
                    return;
                }

                if (!map.hasLayer(markers)) {
                    map.addLayer(markers);
                }

                markers.addLayer(record.marker);
                map.flyTo([record.loc.lat, record.loc.lng], 12, { animate: true, duration: 0.5 });

                setTimeout(function() {
                    if (typeof markers.zoomToShowLayer === 'function') {
                        markers.zoomToShowLayer(record.marker, function() {
                            record.marker.openPopup();
                        });
                    } else {
                        record.marker.openPopup();
                    }
                }, 120);

                setActiveButton(locationId, true);
                updateLocationState(record, historyMode || 'push');
            }

            function findLocationIdFromUrl() {
                var url = new URL(window.location.href);
                var locationId = url.searchParams.get('location');
                var cityValue = normalizeLocationValue(url.searchParams.get('city'));

                if (locationId && markerIndexById[String(locationId)]) {
                    return String(locationId);
                }

                if (cityValue) {
                    for (var i = 0; i < markerRecords.length; i++) {
                        var record = markerRecords[i];
                        if (normalizeLocationValue(record.loc.city) === cityValue || normalizeLocationValue(record.loc.slug) === cityValue) {
                            return String(record.loc.id);
                        }
                    }
                }

                return '';
            }

            map.on('popupopen', function(event) {
                var popupSource = event && event.popup ? event.popup._source : null;
                if (!popupSource) {
                    return;
                }

                var matchedId = null;
                Object.keys(markerIndexById).forEach(function(id) {
                    if (markerIndexById[id] && markerIndexById[id].marker === popupSource) {
                        matchedId = id;
                    }
                });

                if (matchedId) {
                    setActiveButton(matchedId, true);
                    updateLocationState(markerIndexById[matchedId], 'replace');
                }
            });

            (function(){
                var input = document.getElementById('loc-ajax-search');
                var status = document.getElementById('loc-search-status');
                var results = document.getElementById('loc-search-results');
                var timer = null;

                if (!input || !status || !results) return;

                function escapeHtml(str) {
                    return String(str).replace(/[&<>\"']/g, function(tag) {
                        var chars = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
                        return chars[tag] || tag;
                    });
                }

                function filterMapByTerm(term) {
                    var cleaned = String(term || '').trim().toLowerCase();

                    if (!cleaned) {
                        rebuildMarkerLayer(markerRecords, false);
                        return;
                    }

                    var filtered = markerRecords.filter(function(record) {
                        var city = String(record.loc.city || '').toLowerCase();
                        var county = String(record.loc.county || '').toLowerCase();
                        return city.indexOf(cleaned) !== -1 || county.indexOf(cleaned) !== -1;
                    });

                    rebuildMarkerLayer(filtered, true);
                }

                function renderResults(items, term) {
                    results.innerHTML = '';

                    if (!term) {
                        status.textContent = '';
                        filterMapByTerm('');
                        return;
                    }

                    if (!items.length) {
                        status.textContent = 'No locations found.';
                        filterMapByTerm(term);
                        return;
                    }

                    status.textContent = items.length + ' location' + (items.length === 1 ? '' : 's') + ' found.';
                    filterMapByTerm(term);

                    items.forEach(function(item) {
                        var link = document.createElement('a');
                        link.className = 'loc-search-result';
                        link.href = item.url;
                        link.setAttribute('data-location-id', item.id);
                        link.innerHTML = escapeHtml(item.city);
                        link.addEventListener('mouseenter', function() {
                            setActiveButton(item.id, false);
                        });
                        link.addEventListener('click', function(event) {
                            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                                return;
                            }
                            event.preventDefault();
                            focusLocationOnMap(item.id, 'push');
                        });
                        results.appendChild(link);
                    });
                }

                function searchLocations(term) {
                    var formData = new FormData();
                    formData.append('action', 'locations_acf_search_locations');
                    formData.append('nonce', '<?php echo esc_js(wp_create_nonce('locations_acf_search_nonce')); ?>');
                    formData.append('term', term);

                    status.textContent = 'Searching...';

                    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(function(response){ return response.json(); })
                    .then(function(data){
                        if (data && data.success && data.data && Array.isArray(data.data.results)) {
                            renderResults(data.data.results, term);
                        } else {
                            status.textContent = 'Search unavailable right now.';
                        }
                    })
                    .catch(function(){
                        status.textContent = 'Search unavailable right now.';
                    });
                }

                document.querySelectorAll('.loc-button').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var locationId = this.getAttribute('data-location-id');
                        focusLocationOnMap(locationId, 'push');
                    });
                });

                input.addEventListener('input', function(){
                    var term = this.value.trim();
                    clearTimeout(timer);

                    if (!term) {
                        results.innerHTML = '';
                        status.textContent = '';
                        filterMapByTerm('');
                        return;
                    }

                    timer = setTimeout(function(){
                        searchLocations(term);
                    }, 250);
                });

                window.addEventListener('popstate', function() {
                    var urlLocationId = findLocationIdFromUrl();
                    if (urlLocationId) {
                        suppressNextHistoryUpdate = true;
                        focusLocationOnMap(urlLocationId, 'replace');
                        suppressNextHistoryUpdate = false;
                    }
                });

                var initialLocationId = findLocationIdFromUrl();
                if (initialLocationId) {
                    suppressNextHistoryUpdate = true;
                    focusLocationOnMap(initialLocationId, 'replace');
                    suppressNextHistoryUpdate = false;
                } else if (markerRecords.length) {
                    window.history.replaceState({}, '', window.location.href);
                }
            })();
        });
        </script>
        <?php

        return ob_get_clean();
    });
});

/**
 * AJAX search for location archive
 */
add_action('wp_ajax_locations_acf_search_locations', 'locations_acf_search_locations');
add_action('wp_ajax_nopriv_locations_acf_search_locations', 'locations_acf_search_locations');

function locations_acf_search_locations() {
    check_ajax_referer('locations_acf_search_nonce', 'nonce');

    $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
    $term = trim($term);

    $query = new WP_Query([
        'post_type'      => 'lp',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    $results = [];

    if (!empty($query->posts)) {
        foreach ($query->posts as $post_id) {
            $city = locations_acf_get_city($post_id);
            $title = get_the_title($post_id);
            $county = locations_acf_get_county($post_id);

            $haystack = strtolower(trim($city . ' ' . $title . ' ' . $county));
            $needle = strtolower($term);

            if ($needle === '' || strpos($haystack, $needle) !== false) {
                $results[] = [
                    'id'   => $post_id,
                    'city' => $city,
                    'url'  => get_permalink($post_id),
                ];
            }
        }
    }

    wp_send_json_success([
        'results' => array_slice($results, 0, 50),
    ]);
}


function locations_acf_enqueue_random_locations_assets() {
    if (!(is_front_page() || is_home())) return;
    if (!function_exists('get_field') || !get_field('loc_pages', 'option')) return;
    wp_enqueue_style('locations-acf-random-locations', plugin_dir_url(__FILE__) . 'assets/random-locations.min.css', [], '1.8.1');
    wp_enqueue_script('locations-acf-random-locations', plugin_dir_url(__FILE__) . 'assets/random-locations.min.js', [], '1.8.1', true);
}
add_action('wp_enqueue_scripts', 'locations_acf_enqueue_random_locations_assets');

/**
 * Homepage expandable left location icon
 */
add_action('wp_footer', function() {
    if (!function_exists('get_field')) return;
    if (!get_field('loc_pages', 'option')) return;
    if (!(is_front_page() || is_home())) return;

    $archive_page_url = get_post_type_archive_link('lp');
    if (!$archive_page_url) {
        $archive_page_url = home_url('/areas-we-cover/');
    }

    $query = new WP_Query([
        'post_type'      => 'lp',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'rand',
        'no_found_rows'  => true,
    ]);

    if (!$query->have_posts()) return;

    $items = '';
    while ($query->have_posts()) {
        $query->the_post();
        $items .= '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(locations_acf_get_city(get_the_ID())) . '</a></li>';
    }
    wp_reset_postdata();

    echo '<div id="random-locations-box" aria-label="Random locations"><div id="random-locations-handle" role="button" tabindex="0" aria-expanded="false" aria-controls="random-locations-list"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7zM12 11.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg></div><div id="random-locations-list"><p class="random-locations-title"><a href="' . esc_url($archive_page_url) . '">All locations</a></p><ul>' . $items . '</ul></div></div>';
});


/**
 * Front-end SEO output for location pages.
 *
 * Runs at a deliberately very late priority so the Locations ACF meta wins over
 * common SEO plugins. It only affects single Location (`lp`) pages.
 */
define('LOCATIONS_ACF_SEO_PRIORITY', 9999999);

function locations_acf_is_location_singular_request() {
    return !is_admin() && is_singular('lp');
}

function locations_acf_get_location_meta_for_current_request() {
    if (!locations_acf_is_location_singular_request()) {
        return false;
    }

    $post_id = get_queried_object_id();

    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return false;
    }

    return locations_acf_get_effective_meta($post_id);
}

function locations_acf_override_seo_title($title) {
    $meta = locations_acf_get_location_meta_for_current_request();

    if (!$meta || empty($meta['title'])) {
        return $title;
    }

    return $meta['title'];
}

function locations_acf_override_seo_description($description) {
    $meta = locations_acf_get_location_meta_for_current_request();

    if (!$meta || empty($meta['description'])) {
        return $description;
    }

    return $meta['description'];
}

function locations_acf_override_seo_keywords($keywords) {
    $meta = locations_acf_get_location_meta_for_current_request();

    if (!$meta || empty($meta['keywords'])) {
        return $keywords;
    }

    return $meta['keywords'];
}

function locations_acf_build_forced_head_meta_tags($meta) {
    $tags = [];

    if (!empty($meta['title'])) {
        $title = esc_attr($meta['title']);
        $tags[] = '<title>' . esc_html($meta['title']) . '</title>';
        $tags[] = '<meta property="og:title" content="' . $title . '">';
        $tags[] = '<meta name="twitter:title" content="' . $title . '">';
    }

    if (!empty($meta['description'])) {
        $description = esc_attr($meta['description']);
        $tags[] = '<meta name="description" content="' . $description . '">';
        $tags[] = '<meta property="og:description" content="' . $description . '">';
        $tags[] = '<meta name="twitter:description" content="' . $description . '">';
    }

    if (!empty($meta['keywords'])) {
        $tags[] = '<meta name="keywords" content="' . esc_attr($meta['keywords']) . '">';
    }

    if (!$tags) {
        return '';
    }

    return "\n<!-- Locations ACF forced location SEO meta -->\n" . implode("\n", $tags) . "\n<!-- /Locations ACF forced location SEO meta -->\n";
}

function locations_acf_remove_conflicting_head_meta($html) {
    if (!is_string($html) || $html === '') {
        return $html;
    }

    $patterns = [
        '#<title\b[^>]*>.*?</title>#is',
        '#<meta\s+[^>]*(?:name|property)=["\'](?:description|keywords|og:title|og:description|twitter:title|twitter:description)["\'][^>]*>\s*#i',
        '#<meta\s+[^>]*content=["\'][^"\']*["\'][^>]*(?:name|property)=["\'](?:description|keywords|og:title|og:description|twitter:title|twitter:description)["\'][^>]*>\s*#i',
    ];

    return preg_replace($patterns, '', $html);
}

function locations_acf_force_location_meta_in_full_page($html) {
    $meta = locations_acf_get_location_meta_for_current_request();

    if (!$meta || !is_string($html) || $html === '') {
        return $html;
    }

    $forced_tags = locations_acf_build_forced_head_meta_tags($meta);

    if ($forced_tags === '') {
        return $html;
    }

    if (!preg_match('#<head\b[^>]*>.*?</head>#is', $html, $head_match)) {
        return $html;
    }

    $clean_head = locations_acf_remove_conflicting_head_meta($head_match[0]);

    if (stripos($clean_head, '</head>') !== false) {
        $clean_head = preg_replace('#</head>#i', $forced_tags . '</head>', $clean_head, 1);
    }

    return preg_replace('#<head\b[^>]*>.*?</head>#is', $clean_head, $html, 1);
}

// WordPress core title filters.
add_filter('pre_get_document_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wp_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('document_title_parts', function($parts) {
    $meta = locations_acf_get_location_meta_for_current_request();

    if ($meta && !empty($meta['title'])) {
        $parts['title'] = $meta['title'];
        $parts['site'] = '';
        $parts['tagline'] = '';
    }

    return $parts;
}, LOCATIONS_ACF_SEO_PRIORITY);

// Yoast SEO.
add_filter('wpseo_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wpseo_metadesc', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wpseo_metakeywords', 'locations_acf_override_seo_keywords', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wpseo_opengraph_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wpseo_opengraph_desc', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wpseo_twitter_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('wpseo_twitter_description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);

// Rank Math.
add_filter('rank_math/frontend/title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('rank_math/frontend/description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('rank_math/frontend/keywords', 'locations_acf_override_seo_keywords', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('rank_math/opengraph/facebook/title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('rank_math/opengraph/facebook/description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('rank_math/opengraph/twitter/title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('rank_math/opengraph/twitter/description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);

// All in One SEO.
add_filter('aioseo_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('aioseo_description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('aioseo_keywords', 'locations_acf_override_seo_keywords', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('aioseo_facebook_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('aioseo_facebook_description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('aioseo_twitter_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('aioseo_twitter_description', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);

// SEOPress.
add_filter('seopress_titles_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('seopress_titles_desc', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('seopress_titles_keywords', 'locations_acf_override_seo_keywords', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('seopress_social_og_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('seopress_social_og_desc', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('seopress_social_twitter_title', 'locations_acf_override_seo_title', LOCATIONS_ACF_SEO_PRIORITY);
add_filter('seopress_social_twitter_desc', 'locations_acf_override_seo_description', LOCATIONS_ACF_SEO_PRIORITY);

// Extra safety net for plugins/themes that print hard-coded title/meta tags.
add_action('template_redirect', function() {
    if (!locations_acf_is_location_singular_request()) {
        return;
    }

    ob_start('locations_acf_force_location_meta_in_full_page');
}, 0);

/**
 * Template override
 * Auto: detect theme type.
 * Classic: use master-compatible PHP templates.
 * Block: use Gutenberg-compatible PHP templates with block template parts.
 */
add_filter('template_include', function($template) {
    if (!function_exists('get_field')) return $template;
    if (!get_field('loc_pages', 'option')) return $template;

    if (is_singular('lp')) {
        $t = locations_acf_get_template_path('single-lp.php');
        if ($t) return $t;
    }

    if (is_post_type_archive('lp')) {
        $t = locations_acf_get_template_path('archive-lp.php');
        if ($t) return $t;
    }

    return $template;
});