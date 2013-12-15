<?php
/**
 * class-open-graph-protocol-meta.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package open-graph-protocol
 * @since open-graph-protocol 1.0.0
 */

/**
 * Metadata renderer.
 */
class Open_Graph_Protocol_Meta {

	/**
	 * Register action hooks.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'wp_head') );
	}

	/**
	 * Hooked on wp_head to render meta tags in head.
	 */
	public static function wp_head() {

		global $post;
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$metas = array();

		//
		// Basic Metadata
		//

		// title
		include_once( OPEN_GRAPH_PROTOCOL_UTY_LIB . '/class-open-graph-protocol-helper.php' );
		$title = Open_Graph_Protocol_Helper::get_title();
		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
		}
		$metas['og:title'] = $title;

		// type
		if ( is_front_page() ) {
			$type = 'website';
		} else if ( is_home() ) {
			$type = 'blog';
		} else {
			$type = 'article';
		}
		$metas['og:type'] = $type;

		// image
		if ( post_type_supports( $post->post_type, 'thumbnail' ) && has_post_thumbnail() ) {
			list( $src, $width, $height ) = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			if ( ! empty( $src ) ) {
				$metas['og:image'] = $src;
				if ( ! empty( $width ) ) {
					$metas['og:image:width'] = intval( $width );
				}
				if ( ! empty( $height ) ) {
					$metas['og:image:height'] = intval( $height );
				}
			}
		}

		// url
		$metas['og:url'] = $current_url; // using get_permalink() is wrong here for cases like archive or front page

		//
		// More Metadata
		//

		// site_name
		$metas['og:site_name'] = get_bloginfo( 'name' );

		// description
		$description = '';
		if ( is_singular() ) {
			if ( post_type_supports( $post->post_type, 'excerpt' ) ) {
				$description = wp_strip_all_tags( apply_filters( 'get_the_excerpt', $post->post_excerpt ), true );
			}
			if ( empty( $description ) ) {
				$excerpt_length = apply_filters( 'excerpt_length', 55 );
				// This wouldn't be so smart ... for example the default Twenty X
				// themes add a link saying "Continue reading" here which doesn't
				// make sense in the description. Leaving for reference and reminder
				// not to use it.
				//$excerpt_more   = apply_filters( 'excerpt_more', ' ' . '[...]' );
				// wp_trim_words() already applies wp_strip_all_tags() but it doesn't
				// compact whitespace.
				$description = wp_trim_words( wp_strip_all_tags( $post->post_content, true ), $excerpt_length, ' &hellip;' );
			}
		} else if ( is_home() ) {
			$description = get_bloginfo( 'description' );
			if ( empty( $description ) ) {
				$description = get_bloginfo( 'name' );
			}
		} else {
			$what = '';
			if ( is_author() ) {
				$what = __( 'Author', OPEN_GRAPH_PROTOCOL_PLUGIN_DOMAIN );
			} else if ( is_archive() ) {
				$what = __( 'Archive', OPEN_GRAPH_PROTOCOL_PLUGIN_DOMAIN );
			}
			if ( !empty( $what ) ) {
				$description = sprintf( '%s : %s', $what, $title );
			} else {
				$description = $title;
			}
		}
		$metas['og:description'] = $description;

		$metas = apply_filters( 'open_graph_protocol_metas', $metas );

		$m = '';
		foreach( $metas as $property => $content ) {
			$m .= self::render_meta( $property, $content );
		}

		echo apply_filters( 'open_graph_protocol_echo_metas', $m );
	}

	public static function render_meta( $property, $content ) {
		return apply_filters(
			'open_graph_protocol_meta_tag',
			sprintf(
				'<meta property="%s" content="%s" />',
				esc_attr( $property ),
				esc_attr( apply_filters(
					'open_graph_protocol_meta',
					$content,
					$property
				) )
			),
			$property,
			$content
		);
	}

}
Open_Graph_Protocol_Meta::init();
