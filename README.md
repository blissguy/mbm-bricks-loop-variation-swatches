# Bricks Query Loop Variation Swatches

**Contributors:** mixbusmarketing
**Tags:** woocommerce, bricks, variation swatches, product loop, shop
**Requires at least:** 6.0
**Tested up to:** 6.9
**Stable tag:** 2.0.0
**Requires PHP:** 7.4
**Requires Plugins:** woocommerce
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Show your product color, size, and image options as swatches on product cards built with Bricks query loops.

## Description

When shoppers browse your shop page, they can see at a glance which colors, sizes, or materials each product comes in — right on the product card, before they click through.

This plugin adds a **Loop Variation Swatches** element to the Bricks builder. Drop it into any product card inside a query loop, pick which attributes to show, and the element displays them exactly the way you set them up in Bricks' variation swatches: color dots, short labels, or small images.

## Highlights

- Uses the swatch settings you already configured in Bricks — set a color, label, or image per attribute once, and it shows up both on the product page and on your shop cards.
- Falls back to your product variation photos when an option has no image of its own.
- Cap how many swatches show per card and display a "+3" style counter for the rest.
- Hide an attribute on cards unless the product actually offers a choice.
- Style everything visually in the builder: sizes, spacing, borders, labels, and more.
- Works outside query loops too: place it on a single product template (or any page where a product is the current post) and it shows that product's options.

## Requirements

- Bricks theme 2.0 or newer with **Variation swatches** turned on under *Bricks > Settings > WooCommerce*.
- WooCommerce with variable products.

## Getting started

1. Upload the `mbm-bricks-loop-variation-swatches` folder to `/wp-content/plugins/` and activate the plugin.
2. In Bricks, edit your shop or archive layout and add the **Loop Variation Swatches** element inside your product card.
3. In the element settings, choose which attributes to display.

## Where you can use the element

The element is built for product cards inside Bricks query loops, but it is not limited to them:

- **Inside a query loop** — shows the options of each product in the loop. This is the typical shop or archive card setup.
- **Outside a query loop** — shows the options of the current product. Drop it on a single product template (or any page whose current post is a variable product) and it works the same way.

If the current product is not a variable product, or none of the selected attributes apply, the element outputs nothing on the live site. In the builder you'll see a short note explaining why, so you always know what's going on.

## How swatches are resolved

The element follows the same rules as Bricks' own product-page swatches:

- The **swatch type** you set on each WooCommerce attribute (color, label, or image) decides how its options display.
- **Color** options use the term's color, then the attribute's fallback color.
- **Label** options use the term's short label (e.g. "M"), then the attribute's fallback label, then the option name.
- **Image** options use the term's image, then the matching product variation photo (when that fallback is enabled on the attribute), then the attribute's fallback image.
- Options appear in the same order as on your product pages.
- Attributes without a swatch type — including custom per-product attributes — display as text labels.

---

By [Mixbus Marketing](https://mixbusmarketing.com/)
