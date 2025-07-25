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
    
    // Count links and get URLs
    $link_data = yslc_count_links( $content );
    
    // Build custom XML elements
    $custom_elements = sprintf(
        "\t\t<links:internal>%d</links:internal>\n",
        $link_data['internal_count']
    );
    
    // Add internal links list
    if ( ! empty( $link_data['internal_urls'] ) ) {
        $custom_elements .= "\t\t<links:internalList>\n";
        foreach ( $link_data['internal_urls'] as $internal_url ) {
            $custom_elements .= sprintf(
                "\t\t\t<links:url>%s</links:url>\n",
                esc_html( $internal_url )
            );
        }
        $custom_elements .= "\t\t</links:internalList>\n";
    }
    
    $custom_elements .= sprintf(
        "\t\t<links:external>%d</links:external>\n",
        $link_data['external_count']
    );
    
    // Add external links list
    if ( ! empty( $link_data['external_urls'] ) ) {
        $custom_elements .= "\t\t<links:externalList>\n";
        foreach ( $link_data['external_urls'] as $external_url ) {
            $custom_elements .= sprintf(
                "\t\t\t<links:url>%s</links:url>\n",
                esc_html( $external_url )
            );
        }
        $custom_elements .= "\t\t</links:externalList>\n";
    }
    
    // Insert before closing </url> tag
    return str_replace( "\t</url>\n", $custom_elements . "\t</url>\n", $output );
}

/**
 * Count internal and external links in content and return URLs
 */
function yslc_count_links( $content ) {
    // Get all links
    preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );
    
    if ( empty( $matches[1] ) ) {
        return [
            'internal_count' => 0,
            'external_count' => 0,
            'total' => 0,
            'internal_urls' => [],
            'external_urls' => []
        ];
    }
    
    $home_domain = wp_parse_url( home_url(), PHP_URL_HOST );
    $internal_urls = [];
    $external_urls = [];
    
    foreach ( $matches[1] as $href ) {
        // Skip anchors, javascript, mailto, etc.
        if ( strpos( $href, '#' ) === 0 || 
             strpos( $href, 'javascript:' ) === 0 || 
             strpos( $href, 'mailto:' ) === 0 ||
             strpos( $href, 'tel:' ) === 0 ) {
            continue;
        }
        
        // Handle relative URLs - convert to absolute
        if ( strpos( $href, '//' ) === false ) {
            $absolute_url = home_url( $href );
            $internal_urls[] = $absolute_url;
            continue;
        }
        
        // Check domain for absolute URLs
        $link_domain = wp_parse_url( $href, PHP_URL_HOST );
        if ( $link_domain === $home_domain ) {
            $internal_urls[] = $href;
        } else {
            $external_urls[] = $href;
        }
    }
    
    // Remove duplicates
    $internal_urls = array_unique( $internal_urls );
    $external_urls = array_unique( $external_urls );
    
    return [
        'internal_count' => count( $internal_urls ),
        'external_count' => count( $external_urls ),
        'total' => count( $internal_urls ) + count( $external_urls ),
        'internal_urls' => array_values( $internal_urls ),
        'external_urls' => array_values( $external_urls )
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

add_action('admin_menu', function () {
  add_menu_page('All Options', 'All Options', 'manage_options', 'all-options', function () {
    echo '<div style="font-family: monospace; font-size: 12px; line-height: 1.4; padding: 20px;">';
    echo '<h1>WordPress Options (Deserialized)</h1>';
    
    $all_options = wp_load_alloptions();
    
    foreach ($all_options as $option_name => $option_value) {
      echo '<div style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">';
      echo '<strong style="color: #0073aa;">[' . esc_html($option_name) . ']</strong><br>';
      
      // Try to unserialize the value
      $unserialized = @unserialize($option_value);
      
      if ($unserialized !== false || $option_value === 'b:0;') {
        // Value was serialized, show the unserialized version
        echo '<pre style="background: #f0f0f0; padding: 10px; margin: 5px 0; border-radius: 3px; overflow-x: auto;">';
        echo htmlspecialchars( print_r($unserialized, true) );
        echo '</pre>';
      } else {
        // Value was not serialized, show as-is
        echo '<div style="background: #f9f9f9; padding: 10px; margin: 5px 0; border-radius: 3px; word-break: break-all;">';
        echo htmlspecialchars( esc_html($option_value) );
        echo '</div>';
      }
      
      echo '</div>';
    }
    
    echo '</div>';
  });

  add_menu_page('Preview Sitemap', 'Preview Sitemap', 'manage_options', 'preview_sitemap', function () {
    echo '<div style="padding: 20px;">';
    echo '<h1>Raw Sitemap XML Source</h1>';
    
    // Get the sitemap XML content
    $sitemap_url = home_url('/post-sitemap.xml');
    $response = wp_remote_get($sitemap_url);
    
    if (is_wp_error($response)) {
      echo '<p style="color: red;">Error fetching sitemap: ' . $response->get_error_message() . '</p>';
    } else {
      $xml_content = wp_remote_retrieve_body($response);
      echo '<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; white-space: pre-wrap; font-family: monospace; font-size: 12px;">';
      echo htmlspecialchars($xml_content);
      echo '</pre>';
    }
    
    echo '</div>';
  });
});