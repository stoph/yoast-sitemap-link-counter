# Yoast Sitemap Link Counter

A WordPress plugin that extends Yoast SEO sitemaps by adding link count data to each URL entry. This plugin analyzes the content of each post and injects custom XML elements showing internal and external link counts directly into the sitemap. Though, I'm not sure of the actual use case it solves... yet.

## Try it out

**[🚀 Live Demo on WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/stoph/yoast-sitemap-link-counter/refs/heads/main/blueprint.json)**

## What it does

The plugin adds two custom XML elements to each `<url>` entry in your Yoast SEO sitemap:

- `<links:internal>` - Count of links pointing to pages within your domain
- `<links:external>` - Count of links pointing to external domains

## Sample Output

Here's what the enhanced sitemap XML looks like:

```xml
<url>
    <loc>http://example.com/2025/06/19/fake-post-0/</loc>
    <lastmod>2025-06-19T15:27:19+00:00</lastmod>
    <links:internal>2</links:internal>
    <links:external>1</links:external>
</url>
<url>
    <loc>http://example.com/2025/06/19/fake-post-1/</loc>
    <lastmod>2025-06-19T15:27:19+00:00</lastmod>
    <links:internal>4</links:internal>
    <links:external>0</links:external>
</url>
```

## Installation

1. Upload the plugin folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin interface
3. The link counts will automatically appear in your Yoast SEO sitemaps

## Browser Display

To view the new link count columns when viewing the sitemap in a browser, you'll need to replace Yoast's XSL stylesheet file with the enhanced version included in this plugin:

**Copy:** `wp-content/plugins/yoast-link-counter/main-sitemap.xsl`
**To:** `wp-content/plugins/wordpress-seo/css/main-sitemap.xsl`

This will add "Internal Links" and "External Links" columns to the visual sitemap table displayed in web browsers.

## Link Counting Logic

The plugin counts links based on the following rules:

- **Internal links**: Links to the same domain (relative URLs and absolute URLs matching your site's domain)
- **External links**: Links to different domains
- **Excluded**: Internal anchor links (`#section`), JavaScript links, mailto links, and tel links

## Requirements

- WordPress with Yoast SEO plugin active
- PHP 7.4 or higher

## Technical Details

The plugin uses the `wpseo_sitemap_url` filter to process the final XML output for each URL entry. It adds a custom namespace `xmlns:links="https://christophkhouri.com/schemas/links"` to the sitemap for the new elements as validation.

## Author

Christoph Khouri 