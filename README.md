# Locations ACF

Custom WordPress plugin for generating and managing location pages with ACF, archive maps, per-page overrides, AJAX search, front-end SEO output, and block-theme compatible template rendering.

## Version

Current plugin version: **1.7.5**

## Features

- Custom post type: `lp`
- Archive URL: `/areas-we-cover/`
- Automatic location page generation from textarea or CSV import
- Global Locations Options page in WordPress admin
- Per-location override toggle for independent content and meta fields
- Archive map powered by Leaflet with clustered markers
- Shareable archive state with URL sync for selected locations
- Live AJAX location search above the map
- Live map filtering based on the search term
- Clean city-only archive buttons with centered text and stable click behavior
- Dynamic placeholder replacement in text and meta fields
- Block-theme compatible rendering for header and footer in PHP templates
- Homepage expandable locations box with **All Locations** title and archive link

## Plugin Structure

```text
locations-acf-master/
├── README.md
├── data
│   ├── acf-loc-content.txt
│   └── loc-cities-map.csv
├── locations-acf.php
└── templates
    ├── block
    │   ├── archive-lp.php
    │   └── single-lp.php
    └── classic
        ├── archive-lp.php
        └── single-lp.php
```

## ACF Fields

### Global option fields

Managed under **Settings > Locations**:

- `loc_pages`
- `loc_random_text`
- `loc_random_images`
- `loc_meta_title`
- `loc_meta_description`
- `loc_keywords`
- `loc_archive_text`
- `loc_auto_title_mode`
- `loc_title_format`

### Per-location fields

Managed on each `lp` post:

- `loc_main_title`
- `loc_use_individual_settings`
- `loc_random_text`
- `loc_random_images`
- `loc_meta_title`
- `loc_meta_description`
- `loc_keywords`
- `city`
- `county`
- `lat`
- `lng`

## Placeholder Tags

These placeholders are supported in dynamic text and SEO fields:

- `[loc_main_title]`
- `[loc_city]`
- `[loc_county]`
- `[city]`
- `[county]`
- `[title]` for title format mode

## Title Behavior

Global title mode is controlled from the Locations options page:

- **Manual**: uses the location `Main Title` field
- **City**: uses the city name only
- **Format**: uses the `Location Title Code` value

Example format code:

```text
Electricians in [city], [county]
```

## Per-location Override Behavior

When `Use Individual Page Settings` is enabled on a location post:

- single-page text can differ from the global option text
- image galleries can differ per location
- SEO title, description, and keywords can be managed per page

When the toggle is disabled, the plugin falls back to the global option values.

## SEO Output

For single location pages, the plugin outputs:

- document title override via `pre_get_document_title`
- meta description
- meta keywords
- Open Graph title and description
- Twitter title and description

If a field is empty, it is skipped automatically.

## Archive Page

The archive template includes:

- compact AJAX search above the map
- Leaflet map with clustered markers
- city buttons below the map
- shareable selected-city state using `?location=` and `?city=` query params
- search result click focuses the map without forcing a page change
- two-column search results on desktop
- one-column search results on mobile
- live marker filtering when a search term is entered

## Homepage Expandable Locations Section

The plugin includes a homepage locations box that:

- shows a small bold **All Locations** title at the top
- links that title to the location archive page
- displays 10 random location links
- can be expanded to reveal more items where used by the theme or template

The archive link is resolved dynamically from the `lp` post type archive and falls back to:

```text
/areas-we-cover/
```

## Shortcodes

- `[loc_city]`
- `[loc_county]`
- `[loc_main_title]`
- `[lp_google_map]`

Example:

```text
[lp_google_map height="450px" zoom="13"]
```

## Theme Template Mode

Use **Locations > Theme Template Mode** in ACF options:

- **Auto detect theme type** for most sites
- **Old / Classic theme** for older themes using `get_header()` and `get_footer()`
- **Gutenberg / Block theme** for block themes using template parts and block rendering

## Template Rendering Strategy

The plugin now uses separate template folders for theme compatibility:

### `templates/classic/`
Used for classic themes that rely on:

- `get_header()`
- `get_footer()`
- normal PHP theme template flow

### `templates/block/`
Used for block themes where the plugin needs to render theme-compatible output inside PHP templates.

This avoids mixing classic and block rendering logic in the same file and makes template handling more predictable.

## Block Theme Rendering

Version `1.7.5` includes the working block-theme rendering approach for PHP templates.

Instead of relying only on `block_header_area()` and `block_footer_area()`, the plugin supports rendering block patterns and template parts safely inside PHP templates.

For example, this pattern reference:

```html
<!-- wp:pattern {"slug":"header-default","className":"header"} /-->
```

can be rendered in PHP by:

1. parsing the block markup
2. resolving pattern blocks
3. rendering the resolved blocks to HTML

This is useful when the plugin needs to work inside custom PHP templates while still using Gutenberg theme structures.

## Installation

1. Upload the plugin folder to `wp-content/plugins/`
2. Activate **Locations ACF** in the WordPress admin
3. Make sure **Advanced Custom Fields Pro** is active
4. Go to **Settings > Locations** and enable **Locations Pages**
5. Generate location pages from the plugin submenu
6. If needed, resave permalinks after activation or updates

## Notes

- The plugin depends on ACF helper functions and will show an admin notice if ACF is missing
- The custom post type is only registered when `loc_pages` is enabled
- Archive search uses the built-in WordPress AJAX endpoint and requires no external search library
- Leaflet assets are loaded from CDN in the archive template
- For block themes, clear caches after updating templates or template-part rendering logic
- The block and classic template folders are the current supported structure

## Archive URL Sync

When a city is selected from a button, popup, or search result, the archive URL updates with query parameters such as:

```text
/areas-we-cover/?location=123&city=London
```

Opening that URL later will auto-select the same location and reopen it on the map.

## Cluster Behavior

The archive map uses Leaflet MarkerCluster with:

- chunked loading for smoother rendering
- automatic removal of off-screen markers
- clustering disabled once zoomed in close to a single area

## Changelog

### 1.7.5

- improved Gutenberg and block theme compatibility
- split templates into `templates/block/` and `templates/classic/`
- fixed header and footer rendering in PHP template mode
- added safer block rendering strategy for template patterns
- added homepage **All Locations** title with archive link
- improved random location links output for homepage use