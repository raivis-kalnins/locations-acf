<?php
/*
Plugin Name: Locations ACF
Description: Complete locations plugin with ACF integration, CSV upload, shortcodes, Leaflet map, and meta replacement.
Version: 1.2.0
Author: Raivis Kalnins
Requires Plugins: ACF Pro
*/

if (!function_exists('acf_add_options_page')) return;

// Check if locations are enabled
$loc_pages = get_field('loc_pages', 'option') ?? '';
if ($loc_pages !== '1') return; // Only run if enabled

/**
 * Register Locations CPT
 */
function register_location_cpt() {
    register_post_type('lp', [
        'labels' => ['name'=>'Locations','singular_name'=>'Location'],
        'public'=>true,
        'has_archive'=>true,
        'supports'=>['title','editor','thumbnail'],
        'menu_icon'=>'dashicons-location',
        'rewrite'=>['slug'=>'areas-we-cover','with_front'=>false],
        'show_in_rest'=>true,
    ]);
}
add_action('init','register_location_cpt');

/**
 * ACF Options Page
 */
acf_add_options_page([
    'page_title'=>'Locations',
    'menu_title'=>'Locations',
    'menu_slug'=>'locations-settings',
    'capability'=>'manage_options',
    'redirect'=>false
]);

/**
 * ACF Local Fields
 */
if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key'=>'group_generate_locations',
        'title'=>'Generate Locations',
        'fields'=>[
            [
                'key'=>'field_locations_textarea',
                'label'=>'Locations CSV/Textarea',
                'name'=>'locations',
                'type'=>'textarea',
                'instructions'=>'One city per line, pipe-delimited: County|City|Lat|Lng. First row header ignored.',
            ],
            [
                'key'=>'field_upload_csv',
                'label'=>'Upload CSV',
                'name'=>'locations_csv',
                'type'=>'file',
                'instructions'=>'CSV columns: County, City, Lat, Lng (first row header ignored)',
                'return_format'=>'array',
                'mime_types'=>'csv',
            ],
        ],
        'location'=>[[['param'=>'options_page','operator'=>'==','value'=>'locations-settings']]],
    ]);
}

/**
 * Enqueue ACF Scripts & Form Head
 */
add_action('admin_head', function(){
    $screen = get_current_screen();
    if ($screen && $screen->id === 'locations_page_locations-settings') {
        acf_form_head();
        acf_enqueue_scripts();
        acf_enqueue_styles();
    }
});

/**
 * Admin Page Rendering & Processing
 */
add_action('admin_menu', function(){
    add_submenu_page('options-general.php','Generate Locations','Generate Locations','manage_options','locations-settings',function(){
        echo '<div class="wrap" style="max-width:900px;margin:0 auto;padding:2rem;font-family:sans-serif;">';
        echo '<h1 style="font-size:2rem;font-weight:700;margin-bottom:1.5rem;">Generate City Pages</h1>';
        acf_form([
            'post_id'=>'option',
            'field_groups'=>['group_generate_locations'],
            'submit_value'=>'Generate Locations'
        ]);
        echo '</div>';

        if (!empty($_POST['acf']) && check_admin_referer('acf_form','acf_form_nonce')) {
            $fields = $_POST['acf'];
            $lines = [];

            $text_val = $fields['field_locations_textarea'] ?? '';
            if (!empty($text_val)) $lines = explode("\n",$text_val);

            if (!empty($_FILES['acf']['tmp_name']['field_upload_csv'])) {
                $csv_file = $_FILES['acf']['tmp_name']['field_upload_csv'];
                if (($handle=fopen($csv_file,'r'))!==false) {
                    while (($data=fgetcsv($handle,1000,','))!==false) {
                        $lines[]=implode('|',$data);
                    }
                    fclose($handle);
                }
            }

            $created_count = 0;
            $skip_first = true;
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;
                if ($skip_first){ $skip_first=false; continue; }
                $data = str_getcsv($line,'|');
                list($county,$city,$lat,$lng) = array_pad(array_map('trim',$data),4,'');

                if (!empty($city) && !get_page_by_title($city,OBJECT,'lp')) {
                    $post_id = wp_insert_post([
                        'post_title'=>$city,
                        'post_type'=>'lp',
                        'post_status'=>'publish',
                        'post_name'=>sanitize_title($city),
                    ]);
                    if ($post_id && !is_wp_error($post_id)) {
                        foreach(['city','county','lat','lng'] as $field){
                            if (!empty($$field)) update_field($field,$$field,$post_id);
                        }
                        $created_count++;
                    }
                }
            }
            echo '<div class="notice notice-success"><p>Created '.$created_count.' city pages.</p></div>';
        }
    });
});

/**
 * Shortcodes
 */
add_shortcode('loc_city',function(){ return '<span class="loc-city"></span>'; });
add_shortcode('loc_county',function(){ return '<span class="loc-county"></span>'; });

add_shortcode('wp_paginated_lp',function($atts){
    $paged = max(1,get_query_var('paged')?:get_query_var('page')?:1);
    $query = new WP_Query(['post_type'=>'lp','posts_per_page'=>999,'paged'=>$paged,'orderby'=>'title','order'=>'ASC']);
    ob_start();
    echo '<div class="loc-pages_wrap"><ul class="loc-pages_items">';
    if($query->have_posts()){
        while($query->have_posts()){$query->the_post();
            $city = get_field('city') ?: get_the_title();
            echo '<li><h3><a href="'.get_permalink().'">'.esc_html($city).'</a></h3></li>';
        }
    } else echo '<li>No locations found.</li>';
    echo '</ul></div>';
    wp_reset_postdata();
    return ob_get_clean();
});

add_shortcode('lp_google_map',function($atts){
    $atts = shortcode_atts(['id'=>get_the_ID(),'width'=>'100%','height'=>'400px','zoom'=>14],$atts,'lp_google_map');
    $post_id = $atts['id']; $lat=get_field('lat',$post_id); $lng=get_field('lng',$post_id); $city=get_field('city',$post_id); $county=get_field('county',$post_id);
    if(!$lat||!$lng) return '<p>No map coordinates available.</p>';
    $iframe_src=esc_url("https://maps.google.com/maps?q={$lat},{$lng}&z={$atts['zoom']}&output=embed");
    ob_start(); ?>
    <div class="lp-google-map-wrapper" style="margin-bottom:15px;">
        <iframe width="<?php echo esc_attr($atts['width']); ?>" height="<?php echo esc_attr($atts['height']); ?>" frameborder="0" style="border:0;width:100%;height:<?php echo esc_attr($atts['height']); ?>;" src="<?php echo $iframe_src; ?>" allowfullscreen loading="lazy"></iframe>
        <div class="lp-location-info" style="margin-top:10px;font-size:16px;color:#333;">
            <?php if($county):?><div><strong>County:</strong> <?php echo esc_html($county);?></div><?php endif;?>
            <?php if($city):?><div><strong>City:</strong> <?php echo esc_html($city);?></div><?php endif;?>
        </div>
    </div>
    <?php return ob_get_clean();
});

/**
 * Single location map shortcode
 */
add_shortcode('wp_single_lp',function(){
    ob_start(); ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>
    <style>
    #map{height:700px;width:100%;border-radius:8px;}
    .marker-cluster div{background:#e80f0f;border-radius:50%;border:1px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;min-width:38px;min-height:38px;font-weight:bold;font-size:14px;}
    </style>
    <div id="map"></div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
    <script>
    var map = L.map('map',{zoomAnimation:true});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
    var cities = [
    <?php
    $q = new WP_Query(['post_type'=>'lp','posts_per_page'=>-1]);
    $data=[]; while($q->have_posts()){$q->the_post();
        $city=get_field('city')?:get_the_title();
        $lat=get_field('lat'); $lng=get_field('lng'); $url=get_permalink();
        if($lat&&$lng) $data[]='["'.esc_js($city).'",'.$lat.','.$lng.',"'.$url.'"]';
    } wp_reset_postdata();
    echo implode(",",$data);
    ?>
    ];
    var markers = L.markerClusterGroup({spiderfyOnMaxZoom:true,showCoverageOnHover:true,zoomToBoundsOnClick:true,maxClusterRadius:5,animate:true,animateAddingMarkers:true});
    cities.forEach(function(city){
        var name=city[0],lat=city[1],lng=city[2],url=city[3];
        var marker=L.marker([lat,lng]).bindPopup('<strong>'+name+'</strong><br><a href="'+url+'" style="display:inline-block;margin-top:8px;padding:6px 12px;background:#222;color:#fff;text-decoration:none;border-radius:4px;font-size:13px">View page</a>');
        markers.addLayer(marker);
    });
    map.addLayer(markers);
    var bounds=cities.map(c=>[c[1],c[2]]);
    if(bounds.length) map.fitBounds(bounds,{padding:[20,20],maxZoom:18});
    </script>
    <?php return ob_get_clean();
});

/**
 * Meta tags for Location Pages
 */
add_action('template_redirect',function(){
    if(!is_singular('lp')||!function_exists('get_field')) return;
    ob_start(function($buffer){
        $post_id=get_queried_object_id();
        $city=get_field('city',$post_id)??'';
        $title=get_field('loc_meta_title','option')??'';
        $desc=get_field('loc_meta_description','option')??'';
        $keys=get_field('loc_keywords','option')??'';

        if(!empty($title)) $buffer=preg_replace('/<title>[^<]*<\/title>/', '<title>'.$city.' '.$title.'</title>', $buffer);
        if(!empty($desc)) $buffer=preg_replace('/(<meta[^>]*name=["\']description["\'][^>]*content=["\'])[^"\']*/i', '${1}'.$city.' '.$desc, $buffer);
        if(!empty($keys)) $buffer=str_replace('</head>','<meta name="keywords" content="'.$keys.'" />'."\n</head>",$buffer);

        return $buffer;
    });
},999);