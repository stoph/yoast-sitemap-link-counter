<?php
/**
 * Plugin Name: Yoast Sitemap Link Counter
 * Description: Adds link count data to Yoast SEO sitemaps.
 * Version: 1.0
 * Author: Christoph Khouri
 * Requires Plugins: wordpress-seo
 */

// Use the correct filter that processes the final XML output
add_filter( 'wpseo_sitemap_url', 'yslc_add_link_counts_to_sitemap', 10, 2 );
add_filter( 'wpseo_sitemap_index', 'yslc_add_namespace_to_index' );
add_filter( 'wpseo_sitemap_urlset', 'yslc_add_namespace_to_urlset' );

/**
 * Inject custom <links:*> elements into each <url> entry.
 */
function yslc_add_link_counts_to_sitemap( $output, $url ) {
    // Skip if this doesn't look like a post URL
    if ( empty( $url['loc'] ) ) {
        return $output;
    }
    
    // Extract post ID from URL
    $post_id = url_to_postid( $url['loc'] );
    if ( ! $post_id ) {
        return $output;
    }
    
    // Get post content
    $content = get_post_field( 'post_content', $post_id );
    if ( empty( $content ) ) {
        return $output;
    }
    
    // Count links
    $link_counts = yslc_count_links( $content );
    
    // Build custom XML elements
    $custom_elements = sprintf(
        "\t\t<links:internal>%d</links:internal>\n\t\t<links:external>%d</links:external>\n",
        $link_counts['internal'],
        $link_counts['external']
    );
    
    // Insert before closing </url> tag
    return str_replace( "\t</url>\n", $custom_elements . "\t</url>\n", $output );
}

/**
 * Count internal and external links in content
 */
function yslc_count_links( $content ) {
    // Get all links
    preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );
    
    if ( empty( $matches[1] ) ) {
        return [ 'internal' => 0, 'external' => 0, 'total' => 0 ];
    }
    
    $home_domain = wp_parse_url( home_url(), PHP_URL_HOST );
    $internal = 0;
    $external = 0;
    
    foreach ( $matches[1] as $href ) {
        // Skip anchors, javascript, mailto, etc.
        if ( strpos( $href, '#' ) === 0 || 
             strpos( $href, 'javascript:' ) === 0 || 
             strpos( $href, 'mailto:' ) === 0 ||
             strpos( $href, 'tel:' ) === 0 ) {
            continue;
        }
        
        // Handle relative URLs
        if ( strpos( $href, '//' ) === false ) {
            $internal++;
            continue;
        }
        
        // Check domain for absolute URLs
        $link_domain = wp_parse_url( $href, PHP_URL_HOST );
        if ( $link_domain === $home_domain ) {
            $internal++;
        } else {
            $external++;
        }
    }
    
    return [
        'internal' => $internal,
        'external' => $external,
        'total' => $internal + $external
    ];
}

/**
 * Add custom namespace to the <urlset> element.
 */
function yslc_add_namespace_to_urlset( $urlset ) {
  $namespace = 'xmlns:links="https://christophkhouri.com/schemas/links"';
  return str_replace( '<urlset ', "<urlset {$namespace} ", $urlset );
}

/**
 * Add the same namespace to the <sitemapindex> element (optional).
 */
function yslc_add_namespace_to_index( $index ) {
  $namespace = 'xmlns:links="https://christophkhouri.com/schemas/links"';
  return str_replace( '<sitemapindex ', "<sitemapindex {$namespace} ", $index );
}
