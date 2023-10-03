# wordpress-plugin-video-sitemap-generator
Scans published posts for video embeds and generates a video sitemap for Google

Extracts youtube embedded videos only.
Runs once when activated and then every 12 hours.
  
Installation:
1. clone the repo using git clone https://github.com/stephenhodgkiss/wordpress-plugin-video-sitemap-generator.git OR download the zip file.
2. If cloned, compress the folder into a zip file.
3. Upload the plugin folder to the /wp-content/plugins/ directory OR upload the zip file via the WordPress admin panel under PLUGINS > Add New > Upload Plugin.
4. Activate the plugin through the 'Plugins' menu in WordPress.

After the first run you can examine the sitemap_videos.xml in the root director, or view it in the browser e.g. https://stevehodgkiss.net/sitemap_videos.xml

Submit it to search engines such as Google under the Sitemap menu:
https://search.google.com/search-console/
