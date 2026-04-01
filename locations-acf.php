<?php
/*
Plugin Name: Locations ACF
Description: Complete locations plugin with ACF integration, CSV upload, shortcodes, Leaflet map, and meta replacement.
Version: 1.2.7
Author: Raivis Kalnins
Requires Plugins: advanced-custom-fields-pro
Requires at least: 6.0
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) exit;

// Check if ACF is active
if (!function_exists('acf_add_options_page') || !function_exists('acf_add_local_field_group')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Locations ACF:</strong> Advanced Custom Fields (ACF) plugin is required.</p></div>';
    });
    return;
}

/**
 * Register ACF Options Page and Field Groups on ACF init
 */
add_action('acf/init', function() {
    
    // 1. Register Options Page first
    acf_add_options_page([
        'page_title' => 'Locations',
        'menu_title' => 'Locations',
        'menu_slug' => 'locations-settings',
        'parent_slug' => 'options-general.php',
        'capability' => 'edit_posts',
        'redirect' => false,
        'position' => 2,
        'icon_url' => 'dashicons-location',
    ]);
    
    // 2. Register the Locations Settings Field Group (from your JSON)
    acf_add_local_field_group([
        'key' => 'group_68ef6ac20cdfe',
        'title' => 'Locations',
        'fields' => [
            [
                'key' => 'field_69a55b4a01fe8',
                'label' => 'Loc Pages',
                'name' => 'loc_pages',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'message' => '',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ],
            [
                'key' => 'field_69a55b6301fe9',
                'label' => 'Locations',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'placement' => 'top',
                'endpoint' => 0,
            ],
            [
                'key' => 'field_69a55b8701fea',
                'label' => 'Loc Random Text',
                'name' => 'loc_random_text',
                'type' => 'wysiwyg',
                'instructions' => 'Shortcode [loc_county] and [loc_city] for dynamic template',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
            [
                'key' => 'field_69c25edb3748e',
                'label' => 'Loc Archive Intro Text',
                'name' => 'loc_archive_text',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
            [
                'key' => 'field_69bc089d71a64',
                'label' => 'Loc FAQ',
                'name' => 'loc_faq_text',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
            [
                'key' => 'field_69a55b9901feb',
                'label' => 'Loc Random Images',
                'name' => 'loc_random_images',
                'type' => 'gallery',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'return_format' => 'url',
                'library' => 'all',
                'min' => '',
                'max' => '',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
                'insert' => 'append',
                'preview_size' => 'medium',
            ],
            [
                'key' => 'field_69bc113bd8245',
                'label' => 'Loc Meta Title',
                'name' => 'loc_meta_title',
                'type' => 'text',
                'instructions' => 'added before [loc_city] + your text',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => '',
                'maxlength' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ],
            [
                'key' => 'field_69bc1167d8246',
                'label' => 'Loc Meta Description',
                'name' => 'loc_meta_description',
                'type' => 'text',
                'instructions' => 'added before [loc_city] + your text',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => '',
                'maxlength' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ],
            [
                'key' => 'field_69bc117bd8247',
                'label' => 'Loc Keywords',
                'name' => 'loc_keywords',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_69a55b4a01fe8',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => '',
                'maxlength' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'locations-settings',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'description' => '',
    ]);
    
    // 3. Register Location Post Meta Field Group
    acf_add_local_field_group([
        'key' => 'group_location_meta',
        'title' => 'Location Details',
        'fields' => [
            [
                'key' => 'field_city',
                'label' => 'City',
                'name' => 'city',
                'type' => 'text',
                'default_value' => '',
            ],
            [
                'key' => 'field_county',
                'label' => 'County',
                'name' => 'county',
                'type' => 'text',
            ],
            [
                'key' => 'field_lat',
                'label' => 'Latitude',
                'name' => 'lat',
                'type' => 'text',
            ],
            [
                'key' => 'field_lng',
                'label' => 'Longitude',
                'name' => 'lng',
                'type' => 'text',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'lp',
                ]
            ]
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
    
});

/**
 * Plugin Activation Hook - Set default values
 */
register_activation_hook(__FILE__, function() {
    // Set default values for options
    if (function_exists('update_field')) {
        update_field('loc_pages', false, 'option'); // Default OFF
    }
    
    // Flush rewrite rules for CPT
    flush_rewrite_rules();
});

/**
 * Check if locations enabled and register CPT
 */
add_action('init', function() {
    
    // Only register CPT if enabled
    if (function_exists('get_field')) {
        $loc_pages = get_field('loc_pages', 'option');
        if ($loc_pages !== true && $loc_pages !== '1' && $loc_pages !== 1) {
            return; // Not enabled, don't register CPT
        }
    } else {
        return; // ACF not ready
    }
    
    // Register CPT
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
            'not_found' => 'No locations found',
            'not_found_in_trash' => 'No locations found in trash',
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-location',
        'menu_position' => 25,
        'rewrite' => [
            'slug' => 'areas-we-cover',
            'with_front' => false
        ],
        'has_archive' => 'areas-we-cover',
        'show_in_menu' => true,
    ]);
    
}, 20); // Priority 20, after ACF is ready

/**
 * Admin submenu: Generate Locations (only if enabled)
 */
add_action('admin_menu', function () {
    
    // Check if enabled
    if (!function_exists('get_field')) return;
    $loc_pages = get_field('loc_pages', 'option');
    if ($loc_pages !== true && $loc_pages !== '1' && $loc_pages !== 1) return;
    
    add_submenu_page(
        'edit.php?post_type=lp',
        'Generate Locations',
        'Generate Locations',
        'manage_options',
        'generate_locations',
        'render_generate_locations_form'
    );
}, 20);

/**
 * Admin form + CSV/textarea processing
 */
function render_generate_locations_form() {
    ?>
    <div class="wrap" style="max-width:900px; margin:0 auto; padding:2rem; font-family:sans-serif;">
        <h1 style="font-size:2rem; font-weight:700; margin-bottom:1.5rem;">Generate City Pages</h1>
        <form method="post" enctype="multipart/form-data" style="background:#fff; padding:2rem; border-radius:.5rem; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            <?php wp_nonce_field('generate_locations_nonce', 'generate_locations_nonce'); ?>
            <table class="form-table" style="width:100%;">
                <tr>
                    <th><label for="locations">Locations CSV/Textarea</label></th>
                    <td>
                        <textarea name="locations" id="locations" rows="10" style="width:100%; padding:.5rem; border:1px solid #ccc; border-radius:.25rem;" placeholder="County|City|Lat|Lng"></textarea>
                        <p style="font-size:.85rem; color:#555;">One city per line, pipe-delimited: County|City|Lat|Lng. First row header is ignored.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="locations_csv">Or Upload CSV</label></th>
                    <td>
                        <input type="file" name="locations_csv" id="locations_csv" accept=".csv" style="padding:.5rem; border:1px solid #ccc; border-radius:.25rem;" />
                        <p style="font-size:.85rem; color:#555;">CSV columns: County, City, Lat, Lng (first row header ignored)</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Generate Locations'); ?>
        </form>
    </div>
    <?php

    // Process form submission
    if (isset($_POST['generate_locations_nonce']) && 
        check_admin_referer('generate_locations_nonce', 'generate_locations_nonce')) {

        $lines = [];

        // Textarea
        if (!empty($_POST['locations'])) {
            $lines = explode("\n", $_POST['locations']);
        }

        // CSV upload
        if (!empty($_FILES['locations_csv']['tmp_name'])) {
            if (($handle = fopen($_FILES['locations_csv']['tmp_name'], 'r')) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $lines[] = implode('|', $data);
                }
                fclose($handle);
            }
        }

        $created_count = 0;
        $skip_first = true;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            if ($skip_first) { $skip_first = false; continue; }

            $data = str_getcsv($line, '|');
            list($county, $city, $lat, $lng) = array_pad(array_map('trim', $data), 4, '');

            if (!empty($city) && !get_page_by_title($city, OBJECT, 'lp')) {
                $post_id = wp_insert_post([
                    'post_title' => $city,
                    'post_type' => 'lp',
                    'post_status' => 'publish',
                    'post_name' => sanitize_title($city),
                ]);

                if ($post_id && !is_wp_error($post_id)) {
                    update_field('city', $city, $post_id);
                    update_field('county', $county, $post_id);
                    update_field('lat', $lat, $post_id);
                    update_field('lng', $lng, $post_id);
                    $created_count++;
                }
            }
        }

        if ($created_count > 0) {
            echo '<div class="notice notice-success"><p>Created ' . $created_count . ' city pages.</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>No new locations created (duplicates may exist or no data provided).</p></div>';
        }
    }
}

/**
 * Shortcodes - only register if enabled
 */
add_action('init', function() {
    
    // Check if enabled
    if (!function_exists('get_field')) return;
    $loc_pages = get_field('loc_pages', 'option');
    if ($loc_pages !== true && $loc_pages !== '1' && $loc_pages !== 1) return;
    
    // Google Map shortcode
    add_shortcode('lp_google_map', function($atts){
        $atts = shortcode_atts([
            'id' => get_the_ID(),
            'width' => '100%',
            'height' => '400px',
            'zoom' => 14,
        ], $atts, 'lp_google_map');

        $post_id = $atts['id'];
        $lat = get_field('lat', $post_id);
        $lng = get_field('lng', $post_id);
        $city = get_field('city', $post_id);
        $county = get_field('county', $post_id);

        if (!$lat || !$lng) {
            return '<p>No map coordinates available.</p>';
        }

        $iframe_src = esc_url("https://maps.google.com/maps?q={$lat},{$lng}&z={$atts['zoom']}&output=embed");

        ob_start();
        ?>
        <div class="lp-google-map-wrapper" style="margin-bottom:15px;">
            <iframe 
                width="<?php echo esc_attr($atts['width']); ?>" 
                height="<?php echo esc_attr($atts['height']); ?>" 
                frameborder="0" 
                style="border:0; width:100%; height:<?php echo esc_attr($atts['height']); ?>;" 
                src="<?php echo $iframe_src; ?>" 
                allowfullscreen 
                loading="lazy">
            </iframe>
            <div class="lp-location-info" style="margin-top:10px; font-size:16px; color:#333;">
                <?php if ($county): ?>
                    <div><strong>County:</strong> <?php echo esc_html($county); ?></div>
                <?php endif; ?>
                <?php if ($city): ?>
                    <div><strong>City:</strong> <?php echo esc_html($city); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    });

    // City & County shortcodes
    add_shortcode('loc_city', function(){ 
        $city = get_field('city') ?: get_the_title();
        return '<span class="loc-city">' . esc_html($city) . '</span>'; 
    });

    add_shortcode('loc_county', function(){ 
        $county = get_field('county') ?: '';
        return '<span class="loc-county">' . esc_html($county) . '</span>'; 
    });

    // Paginated city list
    add_shortcode('wp_paginated_lp', function($atts){
        $paged = max(1, get_query_var('paged') ?: get_query_var('page') ?: 1);
        
        $query = new WP_Query([
            'post_type' => 'lp',
            'posts_per_page' => 999,
            'paged' => $paged,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ob_start();
        $loc_archive_text = get_field('loc_archive_text', 'option') ?? '';
        echo '<p>'.$loc_archive_text.'</p><div class="loc-pages_wrap"><ul class="loc-pages_items" style="margin-top:70px">';
        
        if ($query->have_posts()) {
            while ($query->have_posts()) : $query->the_post();
                $city = get_field('city') ?: get_the_title();
                $url = get_permalink();
                echo '<li class="loc-pages_item"><h3><a href="' . esc_url($url) . '">' . esc_html($city) . '</a></h3></li>';
            endwhile;
            echo '</ul></div>';
            
            if ($query->max_num_pages > 1) {
                echo '<div class="pagination">' . paginate_links([
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'mid_size' => 2,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]) . '</div>';
            }
        } else {
            echo '<li>No locations found.</li></ul></div>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    });

    // Random city list
    add_shortcode('wp_random_lp_rand_foo', function($atts) {
        if (!is_page('home')) {
            return '';
        }
        $query = new WP_Query([
            'post_type' => 'lp',
            'posts_per_page' => 10,
            'orderby' => 'rand',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="loc-pages_wrap" style="position:absolute;width:fit-content;display:flex;transform:translateX(-50%);left:50%;margin:-40px 0 0 0"><ul class="loc-pages_items" style="display:inline-flex;margin:0">';
            while ($query->have_posts()) : $query->the_post();
                echo '<li class="loc-pages_item" style="color:rgba(255,255,255,0.5);font-size:12px;display:flex;padding-left:10px"><a href="'. esc_url(get_permalink()) .'" style="color:rgba(255,255,255,0.5);font-size:12px">'. esc_html(get_the_title()) .'</a></li>';
            endwhile;
            echo '</ul></div>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    });
    
}, 20);

/**
 * Meta tags for Location Pages
 */
add_action('template_redirect', function() {
    if (!is_singular('lp')) return;
    if (!function_exists('get_field')) return;
    
    // Check if enabled
    $loc_pages = get_field('loc_pages', 'option');
    if ($loc_pages !== true && $loc_pages !== '1' && $loc_pages !== 1) return;
    
    ob_start(function($buffer) {
        $post_id = get_queried_object_id();
        
        $city = get_field('city', $post_id) ?? '';
        $title_raw = get_field('loc_meta_title', 'option');
        $desc_raw = get_field('loc_meta_description', 'option');
        $keys_raw = get_field('loc_keywords', 'option');
        
        $title_clean = !empty($title_raw) ? esc_html(wp_strip_all_tags($title_raw)) : '';
        $desc_clean = !empty($desc_raw) ? esc_attr(wp_strip_all_tags($desc_raw)) : '';
        $keys_clean = !empty($keys_raw) ? esc_attr(wp_strip_all_tags($keys_raw)) : '';
        
        if (!empty($title_clean)) {
            $buffer = preg_replace('/<title>[^<]*<\/title>/', '<title>' . $city .' '. $title_clean . '</title>', $buffer);
            $buffer = preg_replace('/(<meta[^>]*property=["\']og:title["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $title_clean, $buffer);
            $buffer = preg_replace('/(<meta[^>]*name=["\']twitter:title["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $title_clean, $buffer);
        }
        
        if (!empty($desc_clean)) {
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*>/i', $buffer)) {
                $buffer = preg_replace('/(<meta[^>]*name=["\']description["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $desc_clean, $buffer);
            } else {
                $buffer = str_replace('</title>', '</title>' . "\n" . '<meta name="description" content="' . $city .' '. $desc_clean . '" />', $buffer);
            }
            $buffer = preg_replace('/(<meta[^>]*property=["\']og:description["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $desc_clean, $buffer);
            $buffer = preg_replace('/(<meta[^>]*name=["\']twitter:description["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $desc_clean, $buffer);
        }
        
        if (!empty($keys_clean)) {
            $buffer = preg_replace('/<meta[^>]*name=["\']keywords["\'][^>]*>/i', '', $buffer);
            $buffer = str_replace('</head>', '<meta name="keywords" content="' . $keys_clean . '" />' . "\n</head>", $buffer);
        }
        
        return $buffer;
    });
}, 999);

/**
 * Custom CPT archive title for Yoast
 */
function custom_cpt_archive_title($title) {
    if (!function_exists('get_field')) return $title;
    
    $loc_pages = get_field('loc_pages', 'option');
    if ($loc_pages !== true && $loc_pages !== '1' && $loc_pages !== 1) return $title;
    
    $site_name = get_bloginfo('name');
    if (is_post_type_archive('lp')) {
        return 'Locations | '.  $site_name;
    }
    return $title;
}
add_filter('wpseo_title', 'custom_cpt_archive_title', 10, 1);