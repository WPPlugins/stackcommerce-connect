<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}

/**
 * Register the endpoint to receive API calls
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Endpoint {

  /**
   * Sniff API requests
   *
   * @since    1.0.0
   */
  public function sniff() {
    global $wp;

    if( isset( $wp->query_vars['sc-api-version'] ) && isset( $wp->query_vars['sc-api-route'] ) && ! empty( $wp->query_vars['sc-api-version'] ) && ! empty( $wp->query_vars['sc-api-route'] ) ) {
      $sc_api_version = $wp->query_vars['sc-api-version'];
      $sc_api_route = $wp->query_vars['sc-api-route'];

      $sc_fields = json_decode( file_get_contents( 'php://input' ), true );
      $sc_hash = @$_SERVER['HTTP_X_HASH'] ? $_SERVER['HTTP_X_HASH'] : '';
    }

    if( isset( $sc_api_version ) && isset( $sc_api_route ) && isset( $sc_fields ) ) {
      switch( $sc_api_route ) {
        case 'posts':
          $this->authentication( $sc_hash, $sc_fields );
          break;
      }

			exit;
		}
  }

  /**
   * Performs authentication and generate a hash based on post content
   *
   * @since    1.0.0
   */
  protected function authentication( $hash, $request ) {
    if( ! empty( $request ) && $request['post_content']  ) {
      $secret = hash_hmac( 'sha256', $request['post_content'], get_option( 'stackcommerce_wp_secret' ) );

      if( $this->is_hash_valid( $hash, $secret ) ) {
        $stackcommerce_wp_article = new StackCommerce_WP_Article();
        $stackcommerce_wp_article->validate( $request );
      } else {
        return $this->response( 'Hash missing or invalid',
          array(
            'code'        => 'stackcommerce_wp_invalid_hash',
            'status_code' => 400
          )
        );
      }
    } else {
      return $this->response( 'Request is empty or post content is missing',
        array(
          'code'        => 'stackcommerce_wp_empty',
          'status_code' => 400
        )
      );
    }
  }

  /**
   * Makes hash comparison
   *
   * @since    1.1.0
   */
  protected function is_hash_valid( $hash = '', $secret ) {
    if( function_exists( 'hash_equals' ) ) {
      if( ! empty( $hash ) && hash_equals( $hash, $secret ) ) {
        return true;
      } else {
        return false;
      }
    } else {
      if( ! empty( $hash ) && $this->custom_hash_equals( $hash, $secret ) ) {
        return true;
      } else {
        return false;
      }
    }
  }

  /**
   * Custom hash_equals() function for older PHP versions
   * http://php.net/manual/en/function.hash-equals.php#115635
   *
   * @since    1.0.0
   */
  protected function custom_hash_equals( $hash1, $hash2 ) {
    if( strlen( $hash1 ) != strlen( $hash2 ) ) {
      return false;
    } else {
      $res = $hash1 ^ $hash2;
      $ret = 0;
      for( $i = strlen( $res ) - 1; $i >= 0; $i-- ) $ret |= ord( $res[$i] );
      return !$ret;
    }
  }

  /**
   * Send API responses
   *
   * @since    1.0.0
   */
  public function response( $data, $args = array() ) {
    if( is_array( $data ) ) {
      $response = $data;
    } else {
      $response = array(
        'message' => $data
      );

      if( $args['code'] ) {
        $code = array( 'code' => $args['code'] );
        $response = $code + $response;
      }
    }


    if( $args['status_code'] == 200 ) {
      wp_send_json_success( $response );
    } else {
      status_header( $args['status_code'] );
      wp_send_json_error( $response );
    }
  }

}
