<?php
get_header();

// Archive intro text
$loc_archive_text = get_field('loc_archive_text', 'option') ?: '';

// Fetch all locations
$query = new WP_Query([
    'post_type' => 'lp',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
$locations = [];
while ($query->have_posts()): $query->the_post();
    $lat = get_field('lat'); 
    $lng = get_field('lng'); 
    $city = get_the_title(); 
    $url = get_permalink();
    if ($lat && $lng) $locations[] = compact('lat','lng','city','url');
endwhile;
wp_reset_postdata();
?>

<style>
/* Archive wrapper */
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

/* Title and intro */
.loc-archive-title { font-size: 32px; font-weight: 700; margin-bottom: 20px; color: #222; }
.loc-archive-intro { font-size: 18px; color: #555; margin-bottom: 40px; }

/* Location buttons */
.loc-buttons-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 60px;
}
.loc-button {
    flex: 1 1 200px;
    text-align: center;
    padding: 12px 20px;
    background-color: #333; /* dark grey */
    color: #fff;
    font-weight: 600;
    text-decoration: none;
    border-radius: 8px;
    transition: background 0.3s, transform 0.2s;
    max-height: 50px;
    cursor: pointer;
}
.loc-button:hover {
    background-color: #555;
    transform: translateY(-2px);
}

/* Map container */
#lp-map {
    width: 100%;
    height: 600px;
}

/* Map popups and cluster styling */
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

/* Responsive */
@media(max-width:768px){
    .loc-buttons-wrapper { flex-direction: column; }
    #lp-map { height: 400px; }
}
</style>

<div class="loc-archive-wrapper">
    <h1 class="loc-archive-title">Areas We Cover</h1>
    <?php if($loc_archive_text): ?>
        <div class="loc-archive-intro"><?php echo $loc_archive_text; ?></div>
    <?php endif; ?>

    <div class="loc-buttons-wrapper">
        <?php foreach($locations as $index => $loc): ?>
            <div class="loc-button" data-marker-index="<?php echo $index; ?>">
                <?php echo esc_html($loc['city']); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="lp-map"></div>
</div>

<!-- Leaflet + MarkerCluster -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script>
var map = L.map('lp-map', {zoomAnimation:true});
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var markers = L.markerClusterGroup({
    spiderfyOnMaxZoom:true,
    showCoverageOnHover:true,
    zoomToBoundsOnClick:true,
    maxClusterRadius: 40
});

var bounds = [];
var locations = <?php echo json_encode($locations); ?>;
var markerList = []; // store markers for hover

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

// Add markers
locations.forEach(function(loc, index){
    var marker = L.marker([loc.lat, loc.lng], {icon: createRedIcon(25)});
    marker.bindPopup('<strong>'+loc.city+'</strong><br><a href="'+loc.url+'" class="city-popup-link">View page</a>');
    markers.addLayer(marker);
    markerList.push(marker);
    bounds.push([loc.lat, loc.lng]);
});

map.addLayer(markers);
if(bounds.length){ map.fitBounds(bounds,{padding:[20,20],maxZoom:18}); }

// Hover effect: open popup when hovering button
document.querySelectorAll('.loc-button').forEach(function(button){
    button.addEventListener('mouseenter', function(){
        var index = this.dataset.markerIndex;
        var marker = markerList[index];
        marker.openPopup();
        map.setView(marker.getLatLng(), 10);
    });
    button.addEventListener('mouseleave', function(){
        var index = this.dataset.markerIndex;
        var marker = markerList[index];
        marker.closePopup();
    });
    button.addEventListener('click', function(){
        var index = this.dataset.markerIndex;
        var marker = markerList[index];
        map.setView(marker.getLatLng(), 12);
        marker.openPopup();
        window.scrollTo({ top: document.getElementById('lp-map').offsetTop - 20, behavior: 'smooth' });
    });
});
</script>

<?php get_footer(); ?>