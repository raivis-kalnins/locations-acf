<?php
get_header();

$loc_archive_text = get_field('loc_archive_text', 'option') ?: '';

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
    <?php if($loc_archive_text): ?>
        <div class="loc-archive-intro"><?php echo $loc_archive_text; ?></div>
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
</script>

<?php get_footer(); ?>
