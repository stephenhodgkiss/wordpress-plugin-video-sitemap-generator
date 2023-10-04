<?php
/*
Plugin Name: Video Sitemap Generator
Description: Scans published posts for video embeds and generates a video sitemap for Google.
Version: 1.0
Author: Steve Hodgkiss
Author URI: https://stevehodgkiss.net
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Extracts youtube embedded videos only.
 * Runs once when activated and then every 12 hours.
 * 
 * Installation:
 * 1. clone the repo using git clone https://github.com/stephenhodgkiss/wordpress-plugin-video-sitemap-generator.git OR download the zip file.
 * 2. If cloned, compress the folder into a zip file.
 * 3. Upload the plugin folder to the /wp-content/plugins/ directory OR upload the zip file via the WordPress admin panel under PLUGINS > Add New > Upload Plugin.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress.
 */

// Schedule the video scan every 12 hours
function schedule_video_scan()
{
    if (!wp_next_scheduled('video_scan_event')) {
        wp_schedule_event(time(), 'twicedaily', 'video_scan_event');
    }

}
add_action('wp', 'schedule_video_scan');

function scan_posts_for_videos()
{
    // Query for published posts
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    // Initialize an array to store video data
    $video_data = array();

    while ($query->have_posts()) {
        $query->the_post();
        $content = get_the_content();
        $post_tags = get_the_tags();
        $published_date = get_the_date('Y-m-d');

        // Scan for video embeds in the post content (modify this based on your needs)
        // Example: Using a regular expression to find YouTube iframes
        if (preg_match('/<iframe.*?src="https:\/\/www\.youtube\.com\/embed\/(.*?)"/', $content, $matches)) {
            // Gather video data
            $url = get_permalink();

            $video_url = 'https://www.youtube.com/watch?v=' . $matches[1];
            $video_embed = 'https://www.youtube.com/embed/' . $matches[1];

            $thumbnail_url = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            $thumbnail_url = $thumbnail_url[0];

            $excerpt = strip_tags($content); // Remove HTML tags.
            $excerpt = wp_trim_words($excerpt, 200); // Adjust the word count as needed.
            // remove &hellip; from the end of the excerpt
            $excerpt = preg_replace('/&hellip;/', ' ...', $excerpt);
            $title = get_the_title(); // Use post title as the video title
            $description = $excerpt; // Use post excerpt as the video description

            $duration = 0; // Provide the video duration in seconds (if available)

            // using $post_tags to add video tags
            $video_tags = '';
            if ($post_tags) {
                $tags = array();
                foreach ($post_tags as $tag) {
                    $tags[] = $tag->name;
                }
                $video_tags = implode(',#', $tags);
            }

            // replace ' ' with '' in $video_tags
            $video_tags = str_replace(' ', '', $video_tags);
            // replace ',#' with ', ' in $video_tags
            $video_tags = str_replace(',#', ', ', $video_tags);

            // Store video data in the array
            $video_data[] = array(
                'url' => $url,
                'player_loc' => $video_url,
                'content_loc' => $video_embed,
                'thumbnail' => $thumbnail_url,
                'title' => $title,
                'description' => $description,
                'duration' => $duration,
                'tags' => $video_tags,
                'published_date' => $published_date,
            );
        }
    }

    // Generate the video sitemap
    generate_video_sitemap($video_data);

    wp_reset_postdata();
}
add_action('video_scan_event', 'scan_posts_for_videos');

function generate_video_sitemap($video_data)
{

    // Create the sitemap XML
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    // add a timestamp to the sitemap
    $sitemap .= '<!-- Created with WordPress Video Sitemap Generator by Steve Hodgkiss - ' . date('Y-m-d H:i:s') . ' -->' . PHP_EOL;
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
    $sitemap .= 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . PHP_EOL;

    foreach ($video_data as $video) {
        $sitemap .= '<url>' . PHP_EOL;
        $sitemap .= '<loc>' . esc_url($video['url']) . '</loc>' . PHP_EOL;
        $sitemap .= '<video:video>' . PHP_EOL;
        $sitemap .= '<video:thumbnail_loc>' . esc_url($video['thumbnail']) . '</video:thumbnail_loc>' . PHP_EOL;
        $sitemap .= '<video:title>' . esc_html($video['title']) . '</video:title>' . PHP_EOL;
        $sitemap .= '<video:description>' . esc_html($video['description']) . '</video:description>' . PHP_EOL;
        $sitemap .= '<video:duration>' . esc_html($video['duration']) . '</video:duration>' . PHP_EOL;
        $sitemap .= '<video:publication_date>' . esc_html($video['published_date']) . '</video:publication_date>' . PHP_EOL;
        $sitemap .= '<video:tag>' . esc_html($video['tags']) . '</video:tag>' . PHP_EOL;

        $sitemap .= '<video:content_loc>' . esc_url($video['content_loc']) . '</video:content_loc>' . PHP_EOL;

        // Include the metadesc field for player_loc
        $sitemap .= '<video:player_loc allow_embed="yes">';
        $sitemap .= esc_url($video['player_loc']);
        $sitemap .= '</video:player_loc>' . PHP_EOL;

        $sitemap .= '</video:video>' . PHP_EOL;
        $sitemap .= '</url>' . PHP_EOL;
    }

    $sitemap .= '</urlset>' . PHP_EOL;

    // Save the sitemap to a file
    file_put_contents(ABSPATH . 'sitemap_videos.xml', $sitemap);
}

// Register activation hook
register_activation_hook(__FILE__, 'video_sitemap_activation');

function video_sitemap_activation()
{
    // Run the script on activation
    scan_posts_for_videos();
}
