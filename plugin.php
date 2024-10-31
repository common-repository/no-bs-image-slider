<?php

/*
Plugin Name: No BS Image Slider
Plugin URI: https://planetjon.ca/projects/no-bs-image-slider/
Description: For displaying a light-weight image slider
Version: 1.8
Requires at least: 4.7
Tested up to: 5.9
Requires PHP: 5.4
Author: Jonathan Weatherhead
Author URI: https://planetjon.ca
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace planetjon\wordpress\no_bs_image_slider;

use \WP_Widget, \WP_Post;

const default_slide_duration = 5;
const default_transition_duration = 1.25;

function renderSlider( $id, array $slides, array $args = [] ) {
	$sliderID = 'no-bs-image-slider-' . $id;
	$sliderClass = 'no-bs-image-slider';
	$sliderName = $args['name'] ?: '';
	$sliderDescription = $args['description'] ?: '';
	$slideCount = count( $slides );
	$showCounter = filter_var( $args['showCounter'], FILTER_VALIDATE_BOOLEAN );
	$slideDuration = filter_var( $args['slideDuration'], FILTER_VALIDATE_FLOAT, [
		'options' => [ 'default' => default_slide_duration, 'min_range' => 1 ]
	] );
	$transitionDuration = filter_var( $args['transitionDuration'], FILTER_VALIDATE_FLOAT, [
		'options' => [ 'default' => default_transition_duration, 'min_range' => 0 ]
	] );

	if( $showCounter ) {
		$sliderClass .= ' show-counter';
	}

	$sliderKeyframes = '';
	$sliderAnimationName = $sliderID . '-keyframes';
	$sliderAnimationDuration = $slideCount * $slideDuration;
	$transitionDuration = min( $transitionDuration, $slideDuration );
	// Build slider animation keyframes
	for( $i = 0; $i < $slideCount; ++$i ) {
		// Start stationary keyframe for slide
		$sliderKeyframes .= sprintf(
			'%3.2f%%{transform:translateX(-%d%%)}',
			100 * $i / $slideCount,
			100 * $i
		);

		// Start transition keyframe to next slide
		$sliderKeyframes .= sprintf(
			'%3.2f%%{transform:translateX(-%d%%)}',
			100 * ($i + 1 - $transitionDuration / $slideDuration) / $slideCount,
			100 * $i
		);
	}
	// Reset slider to neutral position at the end of animation
	$sliderKeyframes .= '100%{transform:translateX(0%)}';

	// Inject generated slider animation
	printf(
		'<style>#%1$s{--slide-count:%2$d}#%1$s .scrollpane{animation-name:%3$s;animation-duration:%4$ds;}@keyframes %3$s{%5$s}</style>',
		$sliderID,
		$slideCount,
		$sliderAnimationName,
		$sliderAnimationDuration,
		$sliderKeyframes
	);

	// Provide opportunity to inject additional styling
	if( is_callable( $args['stylesCallback'] ) ) {
		call_user_func( $args['stylesCallback'], $id );
	}

	$sliderAria = ['aria-role="region"', 'aria-roledescription="carousel"'];
	if( $sliderDescription ) {
		$sliderAria []= sprintf( 'aria-label="%s"', $sliderDescription );
	}

	// Slider container
	printf(
		'<div id="%1$s" class="%2$s" %3$s><div class="scrollpane" aria-live="off">',
		$sliderID,
		$sliderClass,
		join( ' ', $sliderAria )
	);

	// Slider slides
	$counter = 0;
	foreach( $slides as $slide ) {
		$counter++;
		$id = "nbis-slide-{$slide['id']}";
		$class = "nbis-slide" . ( $slide['class'] ? " {$slide['class']}" : '' );
		$img = $slide['img'];
		$url = $slide['url'];
		$title = $slide['title'];
		$caption = $slide['caption'];
		$aria = [
			'role="group"',
			'aria-roledescription="slide"',
			sprintf( 'aria-label="%d of %d"', $counter,$slideCount )
		];

		printf(
			'<figure id="%1$s" class="%2$s" %7$s><a href="%3$s" title="%4$s"><!-- --></a>%5$s<figcaption>%6$s</figcaption></figure>',
			$id, $class, $url, $title, $img, $caption, join( ' ', $aria )
		);
	}
	echo '</div></div>';
}

function showSlider( $params ) {
	$params = wp_parse_args( $params, [
		'slider' => '',
		'duration' => default_slide_duration,
		'transition' => default_transition_duration,
		'showcounter' => 'no',
		'lazy' => 'no'
	] );

	$params = apply_filters( 'nbis_params', $params );

	$slider = $params['slider'];
	$slideDuration = $params['duration'];
	$transitionDuration = $params['transition'];
	$showCounter = $params['showcounter'];
	$loadingAttribute = filter_var( $params['lazy'], FILTER_VALIDATE_BOOLEAN ) ? 'lazy' : false;

	$sliderInfo = get_term_by( 'slug', $slider, 'no_bs_image_slider' );
	if( !$sliderInfo ) {
		return;
	}

	$slides = get_posts(
		[
			'posts_per_page' => -1,
			'post_type' => 'no_bs_image_slide',
			'order' => 'ASC',
			'tax_query' => [
				[
					'taxonomy' => 'no_bs_image_slider',
					'field' => 'slug',
					'terms' => $slider,
				]
			]
		]
	);

	$slides = apply_filters( 'nbis_slides', $slides );

	if( !$slides ) {
		return;
	}

	$slideDeck = [];
	$imgAttributes = [ 'loading' => $loadingAttribute ];
	foreach( $slides as $idx => $slide ) {
		$slideDeck []= [
			'id' => $slide->ID,
			'img' => get_the_post_thumbnail( $slide, 'full', $imgAttributes ),
			'url' => get_post_meta( $slide->ID, 'nbisl', true ),
			'title' => get_the_title( $slide->ID ),
			'caption' => get_the_excerpt( $slide->ID )
		];
	}

	wp_enqueue_style( 'no-bs-image-slider', plugins_url( 'no-bs-image-slider/style.css' ), [], null );
	renderSlider(
		$slider,
		$slideDeck,
		[
			'description' => $sliderInfo->name,
			'slideDuration' => $slideDuration,
			'transitionDuration' => $transitionDuration,
			'showCounter' => $showCounter,
			'stylesCallback' => function( $slider ) {
				do_action( 'nbis_additional_styles', $slider );
				do_action( 'nbis_additional_styles_' . $slider, $slider );
			}
		]
	);
}

function imageSliderShortcode( $atts ) {
	ob_Start();
	showSlider( $atts );
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

class ImageSliderWidget extends WP_Widget {
	function __construct() {
		$widget_ops = [
			'classname' => 'no-bs-slideshow-widget',
			'description' => 'Show a no bs image slider',
		];

		parent::__construct( 'no-bs-slideshow-widget', 'No BS Image Slider', $widget_ops );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		$slider = $instance['nbis'];
		$slideDuration = $instance['nbisd'];
		$transitionDuration = $instance['nbist'];
		$showCounter = $instance['nbisc'];
		$lazyImages = $instance['nbisl'];

		showSlider( [
			'slider' => $slider,
			'duration' => $slideDuration,
			'transition' => $transitionDuration,
			'showcounter' => $showCounter,
			'lazy' => $lazyImages
		] );

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$sliders = get_terms( [
			'taxonomy' => 'no_bs_image_slider',
			'hide_empty' => false,
		] );

		$slider = $instance['nbis'] ?: '';
		$slideDuration = $instance['nbisd'] ?: default_slide_duration;
		$transitionDuration = $instance['nbist'] ?: default_transition_duration;
		$showCounter = $instance['nbisc'];
		$lazyImages = $instance['nbisl'];

		printf(
			'<p><label>%s <select class="widefat" id="%s" name="%s" value="%s">%s</select></label></p>',
			__( 'Slider', 'no-bs-image-slider' ),
			esc_attr( $this->get_field_id( 'nbis' ) ),
			esc_attr( $this->get_field_name( 'nbis' ) ),
			esc_attr( $slider ),
			array_reduce(
				$sliders,
				function( $c, $e ) use ( $slider ) {
					return $c . sprintf(
						'<option value="%1$s" %3$s>%2$s</option>',
						esc_attr( $e->slug ),
						esc_html( $e->name ),
						selected( $e->slug, $slider, false )
					);
				},
				'<option>Select a slider</option>'
			)
		);

		printf(
			'<p><label>%s <input type="number" min="1" id="%s" name="%s" value="%s" /></label></p>',
			__( 'Slide duration (seconds)', 'no-bs-image-slider' ),
			esc_attr( $this->get_field_id( 'nbisd' ) ),
			esc_attr( $this->get_field_name( 'nbisd' ) ),
			esc_attr( $slideDuration ),
		);

		printf(
			'<p><label>%s <input type="number" min="1" id="%s" name="%s" value="%s" /></label></p>',
			__( 'Slide transition (seconds)', 'no-bs-image-slider' ),
			esc_attr( $this->get_field_id( 'nbist' ) ),
			esc_attr( $this->get_field_name( 'nbist' ) ),
			esc_attr( $transitionDuration ),
		);

		printf(
			'<p><label>%s <input type="checkbox" id="%s" name="%s" value="%s" %s /></label></p>',
			__( 'Show counter?', 'no-bs-image-slider' ),
			esc_attr( $this->get_field_id( 'nbisc' ) ),
			esc_attr( $this->get_field_name( 'nbisc' ) ),
			'yes',
			checked( $showCounter, 'yes', false ),
		);

		printf(
			'<p><label>%s <input type="checkbox" id="%s" name="%s" value="%s" %s /></label></p>',
			__( 'Lazy load images?', 'no-bs-image-slider' ),
			esc_attr( $this->get_field_id( 'nbisl' ) ),
			esc_attr( $this->get_field_name( 'nbisl' ) ),
			'yes',
			checked( $lazyImages, 'yes', false ),
		);
	}

	public function update( $new, $old ) {
		$instance = [
			'nbis' => !empty( $new['nbis'] ) ? sanitize_text_field( $new['nbis'] ) : null,
			'nbisd' => !empty( $new['nbisd'] ) ? sanitize_text_field( $new['nbisd'] ) : null,
			'nbist' => !empty( $new['nbist'] ) ? sanitize_text_field( $new['nbist'] ) : null,
			'nbisc' => !empty( $new['nbisc'] ) ? sanitize_text_field( $new['nbisc'] ) : null,
			'nbisl' => !empty( $new['nbisl'] ) ? sanitize_text_field( $new['nbisl'] ) : null
		];

		return $instance;
	}
}

function createSlidesType() {
	$slideType = [
		'labels' => [
			'name' => __( 'Image Slides' ),
			'singular_name' => __( 'Image Slide' )
		],
		'public' => false,
		'show_ui' => true,
		'supports' => ['title', 'thumbnail', 'excerpt'],
		'register_meta_box_cb' => function( WP_Post $post ) {
			add_meta_box(
				'image_slide_meta',
				'Image Slider Meta',
				function( WP_Post $post, Array $metabox ) {
					$title_field = get_post_meta( $post->ID, 'nbisl', true );
					$outline = sprintf('<label>%s <input type="url" name="nbisl" value="%s"/></label>', esc_html__( 'Link', 'no-bs-image-slider' ), esc_attr( $title_field ) );

					echo $outline;
				}
			);
		}
	];

	register_post_type( 'no_bs_image_slide', $slideType );
}

function saveSlidesType( $postID, $post ) {
	if( 'auto-draft' === $post->post_status || wp_is_post_autosave( $postID ) ) {
		return ;
	}

	$url = filter_input( INPUT_POST, 'nbisl', FILTER_VALIDATE_URL );

	if( $url && current_user_can( 'edit_post', $postID ) ) {
		update_post_meta( $postID, 'nbisl', $url );
	}
}

function createSliderTaxonomy() {
	$sliderTaxonomy = [
		'public' => false,
		'show_ui' => true,
		'labels' => [
			'name' => __( 'Image Sliders' ),
			'singular_name' => __( 'Image Slider' )
		]
	];

	register_taxonomy( 'no_bs_image_slider', null, $sliderTaxonomy );
	register_taxonomy_for_object_type( 'no_bs_image_slider', 'no_bs_image_slide' );
}

add_action( 'init', __NAMESPACE__ . '\createSlidesType' );
add_action( 'init', __NAMESPACE__ . '\createSliderTaxonomy' );
add_action( 'save_post_no_bs_image_slide', __NAMESPACE__ . '\saveSlidesType', 10, 2 );
add_action( 'widgets_init', function() {
	register_widget( __NAMESPACE__ . '\ImageSliderWidget' );
} );
add_shortcode( 'nbis', __NAMESPACE__ . '\imageSliderShortcode' );
