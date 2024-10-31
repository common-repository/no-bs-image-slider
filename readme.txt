=== Plugin Name ===
Contributors: jweathe
Donate link: https://planetjon.ca
Tags: carousel, slideshow, responsive, accessible
Requires at least: 4.7
Tested up to: 5.9
Requires PHP: 5.4
Stable tag: 1.8
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

For displaying a light-weight image slider.

== Description ==

This is an ultra-minimal no-frills responsive and accessible image carousel / slider.

There are many robust image slider plugins already. They work well and have a lot of features, some with even more features at the premium tier.
I'm not saying that they don't have their place but that sometimes they are excessive.

This plugin is for someone who just wants to have linked images scroll by in a loop on their website.

== How To Use ==

No BS Image Slider works by leveraging what WordPress already provides, by creating a new custom post type and taxonomy.

1. To begin, create your slides by using the Image Slide type. Be sure to add a featured image, and provide a link in the link field. Captions can be supplied by filling out the exerpt.
2. Create a slider by adding an Image Slider taxonomy. This allows a slide to be in multiple sliders.
3. Use the No BS Image Slider widget to configure which slider you want displayed, and the duration of each slide.

A great [rundown of the plugin](https://planetjon.ca/4778/making-an-image-carousel-with-no-bs-image-slider "Making an image carousel with No BS Image Slider") is available on the Planetjon blog.

=== Shortcode ===

To manually insert a slider, use the shortcode [nbis].
You must provide at minimum the slider slug.


The shortcode attributes are

* slider="slug"
* duration="number" (seconds, default 5)
* transition="number" (seconds, default 1.25)
* showcounter="yes|no" (default no)
* lazy="yes|no" (default no)

=== Widget ===

A widget that exposes all of the above options is available.

== Frequently Asked Questions ==

= What makes this light-weight? =

No BS Image Slider has minimal backend coding and zero external dependencies.
The slider is also optimized purely with CSS, no JavaScript is used.
The CSS weighs in at under 2 kilobytes, blowing away all of the major slider plugins by a mile.

= How can I set the order of slides in a slider =

While there are no admin options for this, it can be done by using custom CSS.
Sliders are ID'd with `no-bs-image-slider-<slug>` and slides are ID'd with `nbis-slide-<slideId>`.

An approach would be to style the slider scrollpane as Flexbox, and set the order on the slides.
>   \#no-bs-image-slider-slug .scrollpane {
>       display: flex;
>   }
>   \#no-bs-image-slider-slug .scrollpane #nbis-slide-1 {
>       order: 1;
>   }
>   \#no-bs-image-slider-slug .scrollpane #nbis-slide-2 {
>       order: 0;
>   }

== Changelog ==

= 1.8 =
* Transition duration parameter added.
