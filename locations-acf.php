<?php
/*
Plugin Name: Locations ACF
Description: Complete locations plugin with ACF integration, CSV upload, shortcodes, Leaflet map, and meta replacement.
Version: 1.3.0
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
    
    // Options page
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

    // Locations options field group
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
                'default_value' => 0,
            ],
            [
                'key' => 'field_loc_random_text',
                'label' => 'Loc Random Text',
                'name' => 'loc_random_text',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'field_loc_random_images',
                'label' => 'Loc Random Images',
                'name' => 'loc_random_images',
                'type' => 'gallery',
                'return_format' => 'url',
            ],
            [
                'key' => 'field_loc_meta_title',
                'label' => 'Loc Meta Title',
                'name' => 'loc_meta_title',
                'type' => 'text',
            ],
            [
                'key' => 'field_loc_meta_description',
                'label' => 'Loc Meta Description',
                'name' => 'loc_meta_description',
                'type' => 'text',
            ],
            [
                'key' => 'field_loc_keywords',
                'label' => 'Loc Keywords',
                'name' => 'loc_keywords',
                'type' => 'text',
            ],
            [
                'key' => 'field_loc_archive_text',
                'label' => 'Loc Archive Intro Text',
                'name' => 'loc_archive_text',
                'type' => 'wysiwyg',
            ],
        ],
        'location' => [
            [
                ['param' => 'options_page', 'operator' => '==', 'value' => 'locations-settings']
            ]
        ]
    ]);

    // Location CPT Fields
    acf_add_local_field_group([
        'key' => 'group_location_meta',
        'title' => 'Location Details',
        'fields' => [
            ['key'=>'field_city','label'=>'City','name'=>'city','type'=>'text'],
            ['key'=>'field_county','label'=>'County','name'=>'county','type'=>'text'],
            ['key'=>'field_lat','label'=>'Latitude','name'=>'lat','type'=>'text'],
            ['key'=>'field_lng','label'=>'Longitude','name'=>'lng','type'=>'text'],
        ],
        'location' => [[['param'=>'post_type','operator'=>'==','value'=>'lp']]],
    ]);
});

/**
 * Plugin Activation Hook
 */
register_activation_hook(__FILE__, function() {
    if (function_exists('update_field')) {
        update_field('loc_pages', false, 'option');
    }
    flush_rewrite_rules();
});

/**
 * Register CPT if enabled
 */
add_action('init', function() {
    if (!function_exists('get_field')) return;
    $loc_pages = get_field('loc_pages', 'option');
    if (!$loc_pages) return;

    register_post_type('lp', [
        'labels' => [
            'name' => 'Locations','singular_name'=>'Location','menu_name'=>'Locations',
            'add_new'=>'Add New','add_new_item'=>'Add New Location','edit_item'=>'Edit Location',
            'new_item'=>'New Location','view_item'=>'View Location','search_items'=>'Search Locations',
            'not_found'=>'No locations','not_found_in_trash'=>'No locations in trash'
        ],
        'public'=>true,'has_archive'=>'areas-we-cover','supports'=>['title','editor','thumbnail'],
        'menu_icon'=>'dashicons-location','menu_position'=>25,
        'rewrite'=>['slug'=>'areas-we-cover','with_front'=>false],
    ]);
},20);

/**
 * Admin submenu: Generate Locations
 */
add_action('admin_menu', function () {
    if (!function_exists('get_field')) return;
    $loc_pages = get_field('loc_pages', 'option');
    if (!$loc_pages) return;

    add_submenu_page(
        'edit.php?post_type=lp',
        'Generate Locations','Generate Locations','manage_options','generate_locations','render_generate_locations_form'
    );
});

/**
 * Admin form for CSV / textarea
 */
function render_generate_locations_form() {
    ?>
    <div class="wrap" style="max-width:900px;margin:0 auto;padding:2rem;">
        <h1>Generate City Pages</h1>
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
        $lines=[];
        if (!empty($_POST['locations'])) $lines=explode("\n",$_POST['locations']);
        if (!empty($_FILES['locations_csv']['tmp_name'])) {
            if (($handle=fopen($_FILES['locations_csv']['tmp_name'],'r'))!==FALSE) {
                while(($data=fgetcsv($handle,1000,","))!==FALSE) $lines[]=implode('|',$data);
                fclose($handle);
            }
        }
        $created=0;$skip_first=true;
        foreach($lines as $line){
            $line=trim($line);if(!$line) continue;
            if($skip_first){$skip_first=false;continue;}
            $data=str_getcsv($line,'|');list($county,$city,$lat,$lng)=array_pad(array_map('trim',$data),4,'');
            if(!empty($city) && !get_page_by_title($city,OBJECT,'lp')){
                $post_id=wp_insert_post(['post_title'=>$city,'post_type'=>'lp','post_status'=>'publish','post_name'=>sanitize_title($city)]);
                if($post_id && !is_wp_error($post_id)){
                    update_field('city',$city,$post_id);
                    update_field('county',$county,$post_id);
                    update_field('lat',$lat,$post_id);
                    update_field('lng',$lng,$post_id);
                    $created++;
                }
            }
        }
        if($created>0) echo '<div class="notice notice-success"><p>Created '.$created.' city pages.</p></div>';
        else echo '<div class="notice notice-warning"><p>No new locations created.</p></div>';
    }
}

/**
 * Shortcodes
 */
add_action('init', function() {
    if (!function_exists('get_field')) return;
    $loc_pages=get_field('loc_pages','option');if(!$loc_pages) return;

    add_shortcode('loc_city',function(){return '<span class="loc-city">'.esc_html(get_field('city')?:get_the_title()).'</span>';});
    add_shortcode('loc_county',function(){return '<span class="loc-county">'.esc_html(get_field('county')?:'').'</span>';});
    add_shortcode('lp_google_map',function($atts){
        $atts=shortcode_atts(['id'=>get_the_ID(),'width'=>'100%','height'=>'400px','zoom'=>14],$atts,'lp_google_map');
        $post_id=$atts['id'];$lat=get_field('lat',$post_id);$lng=get_field('lng',$post_id);
        if(!$lat || !$lng) return '<p>No map coordinates available.</p>';
        $iframe_src=esc_url("https://maps.google.com/maps?q={$lat},{$lng}&z={$atts['zoom']}&output=embed");
        ob_start(); ?>
        <div class="lp-google-map-wrapper">
            <iframe width="<?php echo esc_attr($atts['width']); ?>" height="<?php echo esc_attr($atts['height']); ?>" frameborder="0" style="border:0;width:100%;height:<?php echo esc_attr($atts['height']); ?>;" src="<?php echo $iframe_src; ?>" allowfullscreen loading="lazy"></iframe>
        </div>
        <?php return ob_get_clean();
    });
});

/**
 * Homepage expandable left 0 location icon
 */
add_action('wp_footer', function() {
    if (!function_exists('get_field')) return;
    $loc_pages=get_field('loc_pages','option');if(!$loc_pages) return;
    if(!is_front_page()) return;

    $query=new WP_Query(['post_type'=>'lp','posts_per_page'=>10,'orderby'=>'rand']);
    if(!$query->have_posts()) return; ?>

    <style>
    #random-locations-box{position:fixed;bottom:100px;left:0;z-index:9999;width:50px;height:50px;background:#333;border-radius:25px;overflow:hidden;transition:width 0.3s;}
    #random-locations-box.open{width:200px;}
    #random-locations-handle{cursor:pointer;width:50px;height:50px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;}
    #random-locations-list{list-style:none;margin:0;padding:10px;display:flex;flex-direction:column;background:#fff;box-shadow:2px 2px 12px rgba(0,0,0,0.2);border-radius:6px;position:absolute;top:0;left:50px;min-width:150px;opacity:0;transform:translateX(-20px);transition:all 0.3s;pointer-events:none;}
    #random-locations-box.open #random-locations-list{opacity:1;transform:translateX(0);pointer-events:auto;}
    #random-locations-list li{margin-bottom:4px;}
    #random-locations-list li a{font-size:12px;color:#333;text-decoration:none;}
    </style>

    <div id="random-locations-box">
        <div id="random-locations-handle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5 14.5 7.62 14.5 9 13.38 11.5 12 11.5z"/>
            </svg>
        </div>
        <ul id="random-locations-list">
            <?php while($query->have_posts()): $query->the_post(); ?>
                <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
            <?php endwhile; ?>
        </ul>
    </div>

    <script>
    (function(){
        const box=document.getElementById('random-locations-box');
        const handle=document.getElementById('random-locations-handle');
        handle.addEventListener('click',function(){box.classList.toggle('open');});
    })();
    </script>

    <?php wp_reset_postdata();
},999);

/**
 * Single template override for plugin (if theme not exists)
 */
add_filter('template_include', function($template){
    if(!function_exists('get_field')) return $template;
    $loc_pages=get_field('loc_pages','option');if(!$loc_pages) return $template;
    if(is_singular('lp')){
        $plugin_template=plugin_dir_path(__FILE__).'templates/single-lp.php';
        if(file_exists($plugin_template)) return $plugin_template;
    }
    if(is_post_type_archive('lp')){
        $plugin_template=plugin_dir_path(__FILE__).'templates/archive-lp.php';
        if(file_exists($plugin_template)) return $plugin_template;
    }
    return $template;
});
