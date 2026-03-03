<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostAnalysis {

	private static $analyzing_posts = array();

	public static function analyzePostOnSave( $post_id, $post, $update ) {
		unset( $update );

		self::analyzePost( $post_id, $post );
	}

	public static function analyzePostAfterInsert( $post_id, $post, $update ) {
		unset( $update );

		self::analyzePost( $post_id, $post );
	}

	private static function analyzePost( $post_id, $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( $post->post_status === 'auto-draft' ) {
			return;
		}

		if ( isset( self::$analyzing_posts[ $post_id ] ) ) {
			return;
		}

		self::$analyzing_posts[ $post_id ] = true;

		$content = self::buildPostContent( $post_id, $post );
		if ( $content === '' ) {
			delete_post_meta( $post_id, Plugin::metaKey() );
			unset( self::$analyzing_posts[ $post_id ] );
			return;
		}

		$analyzer = new \Jidaikobo\A11yc\Analyzer();
		$result   = $analyzer->analyzeHtml(
			self::buildAnalysisDocument( $content, $post ),
			array(
				'url'  => self::postUrl( $post_id ),
				'lang' => \JwpA11y\Compatibility::currentLanguage(),
			)
		);

		$result['analyzed_at'] = current_time( 'mysql' );
		update_post_meta( $post_id, Plugin::metaKey(), $result );
		\JwpA11y\EditorNotices::storePendingNotice( $post_id, $result );
		unset( self::$analyzing_posts[ $post_id ] );
	}

	private static function buildPostContent( $post_id, \WP_Post $post ) {
		$meta_values = '';

		foreach ( get_post_meta( $post_id ) as $meta_key => $meta_value ) {
			if ( ! isset( $meta_key[0] ) || $meta_key[0] === '_' ) {
				continue;
			}

			if ( $meta_key === 'dashi_search' ) {
				continue;
			}

			$meta_values .= isset( $meta_value[0] ) ? wp_specialchars_decode( (string) $meta_value[0] ) : '';
		}

		return apply_filters( 'the_content', $post->post_content ) . $meta_values;
	}

	private static function buildAnalysisDocument( $content, \WP_Post $post ) {
		$lang  = \JwpA11y\Compatibility::currentLanguage();
		$title = get_the_title( $post );
		$title = is_string( $title ) && $title !== '' ? $title : __( 'Untitled', 'jwp_a11y' );

		return '<!doctype html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="utf-8"><title>' .
			esc_html( $title ) .
			'</title></head><body>' . $content . '</body></html>';
	}

	private static function postUrl( $post_id ) {
		$url = get_permalink( $post_id );
		if ( is_string( $url ) && $url !== '' ) {
			return $url;
		}

		return home_url( '/?p=' . $post_id );
	}
}
