<?php
/* 
 * Plugin Name: Locations ACF
 * Description: Works for any ACF field, but designed for use with the Locations plugin. Supports: Gutenberg blocks, Elementor, DIVI Builder, Bakery builder, and any other page builder that can render shortcodes. Example Shortcodes: [wp_paginated_lp] => archive page,  [loc_county] and [loc_city] for dynamic templates
 * Author: WP Engine
 * Version: 1.0
 * Requires PHP: 8.0
*/
$loc_pages = get_field('loc_pages', 'option') ?? ''; //var_dump($loc_pages);

if ($loc_pages == 'true') :

/**
 * Register Locations CPT
 */
function register_location_cpt() {
	register_post_type('lp', [
		'labels' => [
			'name' => 'Locations',
			'singular_name' => 'Location',
		],
		'public' => true,
		'has_archive' => true,
		'supports' => ['title', 'editor', 'thumbnail'],
		'menu_icon' => 'dashicons-location',
		'rewrite' => [
			'slug' => 'areas-we-cover',  // Changed from 'lp' to 'areas-we-cover'
			'with_front' => false
		],
		'has_archive' => 'areas-we-cover',  // Archive slug (different from single)
	]);
}
add_action('init', 'register_location_cpt');

/**
 * Admin submenu: Generate Locations
 */
add_action('admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=lp',
		'Generate Locations',
		'Generate Locations',
		'manage_options',
		'generate_locations',
		'render_generate_locations_blade_form'
	);
});

/**
 * Admin form + CSV/textarea processing
 */
function render_generate_locations_blade_form() {
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

	if (( !empty($_POST['locations']) || !empty($_FILES['locations_csv']['tmp_name']) ) &&
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
		$skip_first = true; // skip header row
		foreach ($lines as $line) {
			$line = trim($line);
			if (!$line) continue;
			if ($skip_first) { $skip_first = false; continue; }

			$data = str_getcsv($line, '|');
			list($county, $city, $lat, $lng) = array_pad(array_map('trim', $data), 4, '');

			$post_id = create_location_post([
				'city' => $city,
				'county' => $county,
				'lat' => $lat,
				'lng' => $lng,
			]);

			if ($post_id) $created_count++;
		}

		echo '<div class="notice notice-success"><p>Created ' . $created_count . ' city pages.</p></div>';
	}
}

/**
 * Create city CPT post
 */
function create_location_post($data) {
	if (empty($data['city'])) return false;

	$title = $data['city'];

	if (get_page_by_title($title, OBJECT, 'lp')) return false;

	$post_id = wp_insert_post([
		'post_title' => $title,
		'post_type' => 'lp',
		'post_status' => 'publish',
		'post_name' => sanitize_title($data['city']), // slug = city only
	]);

	if (is_wp_error($post_id)) return false;

	// Update ACF fields
	foreach (['city','county','lat','lng'] as $field) {
		if (!empty($data[$field])) update_field($field, $data[$field], $post_id);
	}

	return $post_id;
}

/**
 * Google Map shortcode [lp_google_map]
 *  Displays Google Map iframe with city/county info below
 */
add_shortcode('lp_google_map', function($atts){
	// Default shortcode attributes
	$atts = shortcode_atts([
		'id' => get_the_ID(),
		'width' => '100%',
		'height' => '400px',
		'zoom' => 14,
	], $atts, 'lp_google_map');

	$post_id = $atts['id'];
	$lat     = get_field('lat', $post_id);
	$lng     = get_field('lng', $post_id);
	$city    = get_field('city', $post_id);
	$county  = get_field('county', $post_id);

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

		<!-- City & County below map -->
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

/**
 * City & County shortcodes
 */
add_shortcode('loc_city', function(){ return '<span class="loc-city"></span>'; });
add_shortcode('loc_county', function(){ return '<span class="loc-county"></span>'; });

/**
 * Paginated city list [wp_paginated_lp]
 */
add_shortcode('wp_paginated_lp', function($atts) {
	$paged = max(1, get_query_var('paged') ?: get_query_var('page') ?: 1);
	
	// Get actual posts per page from settings (or use reasonable default)
	$posts_per_page = get_option('posts_per_page') ?: 9999;
	
	$query = new WP_Query([
		'post_type' => 'lp',
		'posts_per_page' => 999,
		'paged' => $paged,
		'orderby' => 'title',
		'order' => 'ASC',
	]);

	ob_start();
	$loc_archive_text  = get_field('loc_archive_text', 'option') ?? '';
	echo '<p>'.$loc_archive_text.'</p><div class="loc-pages_wrap"><ul class="loc-pages_items" style="margin-top:70px">';
	if ($query->have_posts()) {
		while ($query->have_posts()) : $query->the_post();
			$city = get_field('city') ?: get_the_title();
			// Use get_permalink() instead of hardcoded URL - works with any rewrite slug
			$url = get_permalink();
			echo '<li class="loc-pages_item"><h3><a href="' . esc_url($url) . '">' . esc_html($city) . '</a></h3></li>';
		endwhile;
		echo '</ul></div>';
		
		// Only show pagination if there's more than one page
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

/**
 * Random city list [wp_random_lp_rand_foo]
 */
add_shortcode('wp_random_lp_rand_foo', function($atts) {
	if ( !is_page('home') ) {
		return ''; // Don't show anywhere else
	}
	$query = new WP_Query([
		'post_type'           => 'lp',
		'posts_per_page'      => 10,
		'orderby'             => 'rand',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true, // performance boost (no pagination needed)
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

endif;

/**
 * NATIVE META TAGS for Location Pages (CPT: 'lp')
 * Overrides Yoast completely on LP pages
 * ACF Options Fields: loc_meta_title, loc_meta_description, loc_keywords
 */

add_action('template_redirect', function() {
	if (!is_singular('lp')) return;
	if (!function_exists('get_field')) return;
	
	ob_start(function($buffer) {
		$post_id = get_queried_object_id();
		
		// Get and process ACF values
		$city    = get_field('city', $post_id) ?? '';
		$title_raw = get_field('loc_meta_title', 'option');
		$desc_raw = get_field('loc_meta_description', 'option');
		$keys_raw = get_field('loc_keywords', 'option');
		
		$title = (!empty($title_raw) && function_exists('lp_replace_tokens')) 
			? lp_replace_tokens($title_raw, $post_id) : $title_raw;
		$desc = (!empty($desc_raw) && function_exists('lp_replace_tokens')) 
			? lp_replace_tokens($desc_raw, $post_id) : $desc_raw;
		$keys = (!empty($keys_raw) && function_exists('lp_replace_tokens')) 
			? lp_replace_tokens($keys_raw, $post_id) : $keys_raw;
		
		$title_clean = !empty($title) ? esc_html(wp_strip_all_tags($title)) : '';
		$desc_clean = !empty($desc) ? esc_attr(wp_strip_all_tags($desc)) : '';
		$keys_clean = !empty($keys) ? esc_attr(wp_strip_all_tags($keys)) : '';
		
		// Title + OpenGraph + Twitter
		if (!empty($title_clean)) {
			$buffer = preg_replace('/<title>[^<]*<\/title>/', '<title>' . $city .' '. $title_clean . '</title>', $buffer);
			$buffer = preg_replace('/(<meta[^>]*property=["\']og:title["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $title_clean, $buffer);
			$buffer = preg_replace('/(<meta[^>]*name=["\']twitter:title["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $title_clean, $buffer);
		}
		
		// Description + OpenGraph + Twitter
		if (!empty($desc_clean)) {
			if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*>/i', $buffer)) {
				$buffer = preg_replace('/(<meta[^>]*name=["\']description["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $desc_clean, $buffer);
			} else {
				$buffer = str_replace('</title>', '</title>' . "\n" . '<meta name="description" content="' . $city .' '. $desc_clean . '" />', $buffer);
			}
			$buffer = preg_replace('/(<meta[^>]*property=["\']og:description["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $desc_clean, $buffer);
			$buffer = preg_replace('/(<meta[^>]*name=["\']twitter:description["\'][^>]*content=["\'])[^"\']*/i', '${1}' . $city .' '. $desc_clean, $buffer);
		}
		
		// Keywords
		if (!empty($keys_clean)) {
			$buffer = preg_replace('/<meta[^>]*name=["\']keywords["\'][^>]*>/i', '', $buffer);
			$buffer = str_replace('</head>', '<meta name="keywords" content="' . $keys_clean . '" />' . "\n</head>", $buffer);
		}
		
		return $buffer;
	});
}, 999);

function custom_cpt_archive_title( $title ) {
	$site_name = get_bloginfo( 'name' );
	// Check if we're on a specific post type archive
	if ( is_post_type_archive( 'lp' ) ) {
		return 'Locations | '.  $site_name;
	}
	return $title;
}
add_filter( 'wpseo_title', 'custom_cpt_archive_title', 10, 1 );


/**
 * Single location page content [wp_single_lp]
 */
add_shortcode('wp_single_lp', function($atts) {
	ob_start();
?>
	<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
	<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
	<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>
	<style>
		#map {
			height: 700px;
			width: 100%;
			border-radius: 8px;
		}
		.leaflet-popup-content-wrapper {
			border-radius: 8px;
		}
		.city-popup-link {
			display: inline-block;
			margin-top: 8px;
			padding: 6px 12px;
			background: #222;
			color: #fff !important;
			text-decoration: none;
			border-radius: 4px;
			font-size: 13px;
		}
		.city-popup-link:hover {
			background: #005a87;
		}
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
		.marker-cluster span {
			color: #fff !important;
			font-weight: bold;
			text-align: center;
		}
	</style>
	<div id="map"></div>
	<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
	<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
	<script>
	// Initialize map
	var map = L.map('map', {zoomAnimation: true});
	// OpenStreetMap tiles
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; OpenStreetMap contributors'
	}).addTo(map);
	// Cities data from PHP
	var cities = [
	<?php
	$city_query = new WP_Query([
		'post_type' => 'lp',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	]);
	$data = [];
	if ($city_query->have_posts()) {
		while ($city_query->have_posts()) {
			$city_query->the_post();
			$city = get_field('city') ?: get_the_title();
			$lat = get_field('lat');
			$lng = get_field('lng');
			$url = get_permalink();

			if ($lat && $lng) {
				$data[] = '["'.esc_js($city).'",'.$lat.','.$lng.',"'.$url.'"]';
			}
		}
		wp_reset_postdata();
	}
	echo implode(",", $data);
	?>
	];
	// Marker cluster group with minimal clustering
	var markers = L.markerClusterGroup({
		spiderfyOnMaxZoom: true,
		showCoverageOnHover: true,
		zoomToBoundsOnClick: true,
		maxClusterRadius: 5, // extremely small → clusters only if exact overlap
		animate: true,
		animateAddingMarkers: true
	});
	// Function to create 25% smaller red pin
	function createRedIcon(size) {
		var newSize = size * 0.75;
		return L.icon({
			iconUrl: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="'+newSize+'" height="'+(newSize*1.64)+'" viewBox="0 0 25 41"><path fill="%23e80f0f" d="M12.5 0C5.6 0 0 5.6 0 12.5c0 10.4 12.5 28.5 12.5 28.5S25 22.9 25 12.5C25 5.6 19.4 0 12.5 0z"/><circle cx="12.5" cy="12.5" r="5.5" fill="white"/></svg>',
			iconSize: [newSize, newSize*1.64],
			iconAnchor: [newSize/2, newSize*1.64],
			popupAnchor: [0, -newSize*0.8],
			shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
			shadowSize: [41, 41]
		});
	}
	// Add markers
	var bounds = [];
	cities.forEach(function(city) {
		var name = city[0];
		var lat = city[1];
		var lng = city[2];
		var url = city[3];

		var size = 25;
		var markerIcon = createRedIcon(size);

		var marker = L.marker([lat, lng], {icon: markerIcon});
		marker.bindPopup('<strong>'+name+'</strong><br><a href="'+url+'" class="city-popup-link">View page</a>');
		marker.bindTooltip(name, {permanent: false, direction: 'top'});
		markers.addLayer(marker);
		bounds.push([lat, lng]);
	});
	map.addLayer(markers);
	// Fit bounds with maxZoom extremely high to see all pins
	if (bounds.length) {
		map.fitBounds(bounds, {padding: [20,20], maxZoom: 18}); // allows zoom in as much as needed
	}
	</script>
<?php
	return ob_get_clean();
});

/**
 * Footer Global - Locations - before </body>
 */
add_action('wp_footer', 'footer_global');

function footer_global() {
	global $post;
?>
<!-- Footer Loc Global -->
	<?php if ( get_fields('option')['loc_pages'] == 'yes' ) { echo do_shortcode('[wp_random_lp_rand_foo]'); } ?>
<!-- /Footer Loc Global -->
<?php
};
