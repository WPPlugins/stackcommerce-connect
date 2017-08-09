<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}

define( 'SCWP_SEARCH_MIN_LENGTH', 3 );

/**
 * Create RESTful search endpoints of taxonomies
 *
 * @since      1.1.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Search {

  /**
   * Sniff requests to custom search endpoint
   *
   * @since    1.1.0
   */
  public function sniff() {
    if( isset( $_POST['taxonomy'] ) && isset( $_POST['q'] ) ) {
      $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
      $term = $this->sanitize( $_POST['q'] );
    }

    if( isset( $taxonomy ) && isset( $term ) && strlen( $term ) >= SCWP_SEARCH_MIN_LENGTH ) {
      if( $taxonomy == 'categories' ) {
        $this->categories( $term );
      } else if( $taxonomy == 'tags' ) {
        $this->tags( $term );
      } else {
        $this->response( 'Given taxonomy type is invalid or empty', 400, 'stackcommerce_wp_invalid_taxonomy_type' );
      }
    }
  }

  /**
   * Sanitize given term
   *
   * @since    1.1.0
   */
  private function sanitize( $term = null ) {
    if( strlen( $term ) >= SCWP_SEARCH_MIN_LENGTH ) {
      $term = sanitize_text_field( $term );
    } else {
      $term = '';
    }

    return $term;
  }

  /**
   * Perform category search
   *
   * @since    1.1.0
   */
  private function categories( $term ) {
    $args = array(
      'name__like' => $term,
      'hide_empty' => false,
    );

    $categories_search = get_categories( $args );

    $categories = array();

    foreach( $categories_search as $cat ) {
      $cat_result = array( 'id' => $cat->slug, 'text' => $cat->name );

      array_push( $categories, $cat_result );
    }

    $response = array( 'categories' => $categories );
    $this->response( $response );
  }

  /**
   * Perform tag search
   *
   * @since    1.1.0
   */
  private function tags( $term ) {
    $args = array(
      'name__like' => $term,
      'hide_empty' => false,
    );

    $tags_search = get_tags( $args );

    $tags = array();

    foreach( $tags_search as $tag ) {
      $tag_result = array( 'id' => $tag->slug, 'text' => $tag->name );

      array_push( $tags, $tag_result );
    }

    $response = array( 'tags' => $tags );
    $this->response( $response );
  }

  /**
   * Receive and return a response
   *
   * @since    1.1.0
   */
  private function response( $response, $status_code = 200, $error_code = null ) {
    $stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

    if( $error_code ) {
      $options = array(
        'code'        => $error_code,
        'status_code' => $status_code
      );
    } else {
      $options = array(
        'status_code' => $status_code
      );
    }

    return $stackcommerce_wp_endpoint->response( $response, $options );
  }
}
