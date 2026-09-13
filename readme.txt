=== Pluximo Image Optimizer ===
Contributors: pluximo
Tags: png to webp, png to avif, image optimization, webp, avif
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically convert new or existing PNG images to WebP or AVIF, with quality controls, transparency preservation, bulk conversion, and optional backups.

== Description ==

Pluximo Image Optimizer optimizes WordPress images by converting PNG files into modern WebP or AVIF formats. Convert new uploads automatically or process existing Media Library images in bulk, while preserving alpha transparency and controlling output quality.

= Key Features =

* **WebP or AVIF Output:** Choose the modern image format that best fits your site and server capabilities.
* **Automatic Upload Conversion:** Instantly converts newly uploaded PNG images into optimized WebP or AVIF files.
* **Bulk Converter:** Scan and convert existing PNG images in your WordPress Media Library with live progress logs.
* **Quality & Transparency Controls:** Set compression quality from 1-100 while preserving alpha transparency.
* **Backup Option:** Optionally keep original PNG backups in `wp-content/uploads/pluximo-image-optimizer-backups/`.
* **Multi-driver Support:** Supports GD Library, ImageMagick (Imagick), and WP_Image_Editor fallback engines.
* **Multi-lingual Support:** Fully translated into English, Mandarin Chinese, Spanish, Hindi, French, and Arabic.

== Installation ==

1. Upload the `pluximo-image-optimizer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Pluximo > PNG Optimizer** to configure the output format and quality or run a bulk conversion.

== Frequently Asked Questions ==

= Does it convert existing PNG images in the Media Library? =
Yes! You can use the Bulk Converter section on the plugin settings page to scan and convert existing PNG images.

= Can I keep the original PNG files as backup? =
Yes. Simply check the "Keep Original PNG Backup" option in the plugin settings.

== Changelog ==

= 1.0.0 =
* Initial release of Pluximo Image Optimizer with automatic upload conversion, bulk converter, customizable compression quality, and modular Pluximo ecosystem integration.
