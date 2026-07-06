=== Bricks Query Loop Variation Swatches ===
Contributors: mixbusmarketing
Tags: woocommerce, bricks, variation swatches, product loop, shop
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your product color, size, and image options as swatches on product cards built with Bricks query loops.

== Description ==

When shoppers browse your shop page, they can see at a glance which colors, sizes, or materials each product comes in — right on the product card, before they click through.

This plugin adds a "Loop Variation Swatches" element to the Bricks builder. Drop it into any product card inside a query loop, pick which attributes to show, and the element displays them exactly the way you set them up in Bricks' variation swatches: color dots, short labels, or small images.

**Highlights**

* Uses the swatch settings you already configured in Bricks — set a color, label, or image per attribute once, and it shows up both on the product page and on your shop cards.
* Falls back to your product variation photos when an option has no image of its own.
* Cap how many swatches show per card and display a "+3" style counter for the rest.
* Hide an attribute on cards unless the product actually offers a choice.
* Attribute labels automatically switch between singular and plural ("Color" vs "Colors") based on how many options the product has — with optional text overrides per attribute if you want different wording.
* Style everything visually in the builder: sizes, spacing, borders, labels, and more.
* Works outside query loops too: place it on a single product template (or any page where a product is the current post) and it shows that product's options.

**Requirements**

* Bricks theme 2.0 or newer with "Variation swatches" turned on under Bricks > Settings > WooCommerce.
* WooCommerce with variable products.

== Installation ==

1. Upload the `mbm-bricks-loop-variation-swatches` folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. In Bricks, edit your shop or archive layout and add the "Loop Variation Swatches" element inside your product card.
4. In the element settings, choose which attributes to display.

== Changelog ==

= 2.1.2 =
* Fixed: the "Min values to show" setting could hide an attribute entirely in the Bricks builder, leaving nothing to select or style. It now only affects the frontend — the attribute always stays visible while editing.

= 2.1.1 =
* Removed the manual translation loading call — WordPress has auto-loaded plugin translations since version 4.6, so this is no longer needed.
* Confirmed compatibility with the latest WordPress version.

= 2.1.0 =
* Added a "Label alignment" style control so the attribute label can be vertically aligned against the swatch list when the label sits beside it.
* Attribute group labels now switch between singular and plural automatically ("Color" when a product has one option, "Colors" when it has several), with optional per-attribute text overrides.

= 2.0.0 =
* Complete rebuild on Bricks' own variation swatches system: the swatch type you pick per attribute (color, label, or image) now decides how every option is displayed.
* The element now also works outside query loops — on a single product template it shows the current product's options.
* Short labels you set per option (like "M" instead of "Medium") now show correctly.
* Options with no image now use the matching product variation photo, then your fallback image — the same order Bricks uses on the product page.
* Swatches now appear in the same order as on your product pages.
* All styling controls now live in the Style tab, and the general style options (background, border, spacing) now work.
* Product cards without matching options no longer output empty markup.
* Cleaner, larger default swatch styling.

= 1.0.0 =
* First release.
