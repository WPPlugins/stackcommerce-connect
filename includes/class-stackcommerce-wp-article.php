<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}

/**
 * Validate, sanitize and insert articles
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Article {

  /**
   * Validate article fields
   *
   * @since    1.0.0
   */
  public function validate( $fields ) {
    $stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();
    $errors = array();

    if( ! array_key_exists( 'post_title', $fields ) || strlen( wp_strip_all_tags( $fields['post_title'] ) ) == 0 ) {
      array_push( $errors, 'Title field cannot be empty.' );
    }

    if( ! array_key_exists( 'post_content', $fields ) || strlen( $fields['post_content'] ) == 0 ) {
      array_push( $errors, 'Content field cannot be empty.' );
    }

    if( empty( $errors ) ) {
      $this->insert( $fields );
    } else {
      $request_errors = '';

      foreach( $errors as $error ) {
        $request_errors .= ' ' . $error;
      }

      return $stackcommerce_wp_endpoint->response( $request_errors,
        array(
          'code'        => 'stackcommerce_wp_missing_fields',
          'status_code' => 400
        )
      );
    }
  }

  /**
   * Get admin fields
   *
   * @since    1.0.0
   */
  protected function get_admin_fields( $name ) {
    switch( $name ) {
      case 'post_author':
        return intval( implode( get_option( 'stackcommerce_wp_author' ) ) );
        break;
      case 'post_status':
        $post_status = ['draft', 'pending', 'future'];
        $post_status_option = intval( implode( get_option( 'stackcommerce_wp_post_status' ) ) );

        return $post_status[$post_status_option];
        break;
      case 'post_categories':
        return get_option( 'stackcommerce_wp_categories' );
        break;
      case 'post_tags':
        return get_option( 'stackcommerce_wp_tags' );
        break;
      case 'featured_image':
        return implode( get_option( 'stackcommerce_wp_featured_image' ) );
        break;
    }
  }

  /**
   * Get categories IDs
   *
   * @since    1.1.0
   */
  protected function get_categories_ids( $categories ) {
    if ( empty( $categories ) ) return false;

    $categories_ids = [];

    foreach( $categories as $category ) {
      $category_id = get_category_by_slug( $category );

      array_push( $categories_ids,  $category_id->term_id );
    }

    return $categories_ids;
  }

  /**
   * Schedule post and change its status
   *
   * @since    1.0.0
   */
  protected function schedule_post( $post, $fields ) {
    if( empty( $fields['post_date_gmt'] ) ) return $post;

    $post['post_date_gmt'] = get_gmt_from_date( $fields['post_date_gmt'] );
    $post['post_status'] = 'future';

    return $post;
  }

  /**
   * Returns image mime types users are allowed to upload via the API
   *
   * @since    1.1.0
   * @return array
   */
  protected function allowed_image_mime_types() {
  	return array(
  		'jpg|jpeg|jpe' => 'image/jpeg',
  		'gif'          => 'image/gif',
  		'png'          => 'image/png',
  		'bmp'          => 'image/bmp',
  		'tiff|tif'     => 'image/tiff',
  		'ico'          => 'image/x-icon',
  	);
  }

  /**
   * Upload image from URL
   *
   * @since    1.1.0
   * @param string $image_url
   * @return array|StackCommerce_WP_Endpoint->response attachment data or error message
   */
  protected function upload_image_from_url( $image_url ) {
    $stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

    $file_name  = basename( current( explode( '?', $image_url ) ) );
  	$parsed_url = @parse_url( $image_url );

    $errors = array();

    // Check parsed URL.
  	if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
      $data = sprintf( 'Invalid URL %s', $image_url );
      $error_args = array(
        'code'        => 'stackcommerce_wp_invalid_image_url',
        'status_code' => 400
      );

      $error = array( $data, $error_args );

      $stackcommerce_wp_endpoint->response( $data, $error_args );
      array_push( $errors, $error );
  	}

    // Ensure url is valid
  	$safe_image_url = esc_url_raw( $image_url );

    // Get the file
  	$response = wp_safe_remote_get( $safe_image_url, array(
  		'timeout' => 20,
  	) );

    if ( is_wp_error( $response ) ) {
      $data = sprintf( 'Error getting remote image %s.', $image_url ) . ' ' . sprintf( 'Error: %s', $response->get_error_message() );
      $error_args = array(
        'code'        => 'stackcommerce_wp_invalid_remote_image_url',
        'status_code' => 400
      );

      $error = array( $data, $error_args );

      $stackcommerce_wp_endpoint->response( $data, $error_args );
      array_push( $errors, $error );
   	} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
      $data = sprintf( 'Error getting remote image %s', $image_url );
      $error_args = array(
        'code'        => 'stackcommerce_wp_invalid_remote_image_url',
        'status_code' => 400
      );

      $error = array( $data, $error_args );

      $stackcommerce_wp_endpoint->response( $data, $error_args );
      array_push( $errors, $error );
  	}

  	// Ensure we have a file name and type
  	$wp_filetype = wp_check_filetype( $file_name, $this->allowed_image_mime_types() );

    if ( ! $wp_filetype['type'] ) {
  		$headers = wp_remote_retrieve_headers( $response );

  		if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
  			$disposition = end( explode( 'filename=', $headers['content-disposition'] ) );
  			$disposition = sanitize_file_name( $disposition );
  			$file_name   = $disposition;
  		} elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
  			$file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
  		}
  		unset( $headers );

  		// Recheck filetype
  		$wp_filetype = wp_check_filetype( $file_name, $this->allowed_image_mime_types() );

      if ( ! $wp_filetype['type'] ) {
        $data = sprintf( 'Invalid image type: %s', $image_url );
        $error_args = array(
          'code'        => 'stackcommerce_wp_invalid_image_type',
          'status_code' => 400
        );

        $error = array( $data, $error_args );

        $stackcommerce_wp_endpoint->response( $data, $error_args );
        array_push( $errors, $error );
  		}
  	}

  	// Upload the file
  	$upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

    if ( $upload['error'] ) {
      $data = $upload['error'];
      $error_args = array(
        'code'        => 'stackcommerce_wp_image_upload_error',
        'status_code' => 400
      );

      $error = array( $data, $error_args );

      $stackcommerce_wp_endpoint->response( $data, $error_args );
      array_push( $errors, $error );
  	}

  	// Get filesize
  	$filesize = filesize( $upload['file'] );
  	if ( 0 == $filesize ) {
  		@unlink( $upload['file'] );
  		unset( $upload );

      $data = sprintf( 'Zero size file downloaded: %s', $image_url );
      $error_args = array(
        'code'        => 'stackcommerce_wp_image_upload_file_error',
        'status_code' => 400
      );

      $error = array( $data, $error_args );

      $stackcommerce_wp_endpoint->response( $data, $error_args );
      array_push( $errors, $error );
  	}

    if ( count( $errors ) > 0 ) {
      $upload['error'] = $errors;
    }

  	return $upload;
  }

  /**
   * Set uploaded image as attachment
   *
   * @since 1.1.0
   * @param array $upload Upload information from wp_upload_bits
   * @param int $id Post ID. Default to 0
   * @return int Attachment ID
   */
  function set_uploaded_image_as_attachment( $upload, $id = 0 ) {
  	$info    = wp_check_filetype( $upload['file'] );
  	$title   = '';
  	$content = '';
    $post_author = $this->get_admin_fields( 'post_author' );

  	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
  		include_once( ABSPATH . 'wp-admin/includes/image.php' );
  	}

  	if ( $image_meta = wp_read_image_metadata( $upload['file'] ) ) {
  		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
  			$title = sanitize_text_field( $image_meta['title'] );
  		}

  		if ( trim( $image_meta['caption'] ) ) {
  			$content = sanitize_text_field( $image_meta['caption'] );
  		}
  	}

  	$attachment = array(
  		'post_mime_type' => $info['type'],
  		'guid'           => $upload['url'],
  		'post_parent'    => $id,
  		'post_title'     => $title,
  		'post_content'   => $content,
      'post_author'    => $post_author,
  	);

  	$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );

    if ( ! is_wp_error( $attachment_id ) ) {
  		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
  	}

  	return $attachment_id;
  }

  /**
   * Set featured media to a post
   *
   * @since    1.0.0
   */
  protected function set_featured_media( $attachment_id, $post_id ) {
    return update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
  }

  /**
   * Strip first image from post content
   *
   * @since    1.0.0
   */
  protected function strip_image( $post ) {
    $post['post_content'] = preg_replace( '/<img.*?src="([^">]*\/([^">]*?))".*?>/', '', $post['post_content'], 1 );

    return $post;
  }

  /**
   * Check if matches the last created post to prevent duplications
   *
   * @since    1.0.0
   */
  protected function check_duplicate( $post ) {
    $stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

    $posts = wp_get_recent_posts( array( 'numberposts' => 1 ), ARRAY_A );

    $post_title = $post['post_title'][0];
    $post_content = $post['post_content'][0];

    if( array_key_exists( 'post_title', $posts ) && array_key_exists( 'post_content', $posts ) ) {
      $last_post_title = $posts[0]['post_title'];
      $last_post_content = $posts[0]['post_content'];
    }

    if( isset( $last_post_title ) && isset( $last_post_content ) ) {
      $post_title_check = ($post_title == $last_post_title);
      $post_content_check = ($post_content == $last_post_content);

      if( $post_title_check && $post_content_check ) {
        return $stackcommerce_wp_endpoint->response( 'Post cannot be posted because it has been recently published',
          array(
            'code'        => 'stackcommerce_wp_already_posted',
            'status_code' => 400
          )
        );
      }
    }
  }

  /**
   * Prepare post to be inserted
   *
   * @since    1.0.0
   */
  protected function prepare( $fields ) {
    $post = array(
      'post_title'     => wp_strip_all_tags( $fields['post_title'] ),
      'post_content'   => $fields['post_content'],
      'post_type'      => 'post',
      'post_author'    => $this->get_admin_fields( 'post_author' ),
      'post_status'    => $this->get_admin_fields( 'post_status' )
    );

    if( array_key_exists( 'post_name', $fields ) ) {
      $post['post_name'] = $fields['post_name'];
    }

    if( array_key_exists( 'post_excerpt', $fields ) ) {
      $post['post_excerpt'] = $fields['post_excerpt'];
    }

    $raw_categories = $this->get_admin_fields( 'post_categories' );
    $raw_tags = $this->get_admin_fields( 'post_tags' );

    if( is_array( $raw_categories ) && ! empty( $raw_categories ) ) {
      $categories = $this->get_categories_ids( $raw_categories );
    }

    if( is_array( $raw_tags ) && ! empty( $raw_tags ) ) {
      $tags = $raw_tags;
    }

    if( isset( $categories ) ) {
      $post['post_category'] = $categories;
    }

    if( isset( $tags ) ) {
      $post['tags_input'] = $tags;
    }

    return $post;
  }

  /**
   * Insert new posts
   *
   * @since    1.0.0
   */
  protected function insert( $fields ) {
    $errors = [];
    $stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

    $post = $this->prepare( $fields );

    $this->check_duplicate( $fields );

    if( array_key_exists( 'post_date_gmt', $fields ) ) {
      $post = $this->schedule_post( $post, $fields );
    }

    $featured_image_options = $this->get_admin_fields( 'featured_image' );

    switch( $featured_image_options ) {
      case 'featured_image_only':
        $post = $this->strip_image( $post );
        break;
      case 'no_featured_image':
        unset( $fields['featured_media'] );
        unset( $post['featured_media'] );
        break;
    }

    if( array_key_exists( 'featured_media', $fields ) ) {
      $upload_image = $this->upload_image_from_url( $fields['featured_media'] );

      if( array_key_exists( 'error', $upload_image ) && ! empty( $upload_image['error'] ) ) {
        $featured_image_errors = true;
      }
    }

    if( ! isset( $featured_image_errors ) ) {
      $post_id = wp_insert_post( $post );

      if( $post_id ) {
        if( array_key_exists( 'url', $upload_image ) ) {
          $attachment_id = $this->set_uploaded_image_as_attachment( $upload_image, $post_id );
          $featured_media = $this->set_featured_media( $attachment_id, $post_id );

          $post['featured_media'] = $featured_media;
        }

        return $stackcommerce_wp_endpoint->response( $post,
          array(
            'status_code' => 200
          )
        );
      } else {
        return $stackcommerce_wp_endpoint->response( 'An error occurred while creating post',
          array(
            'code'        => 'stackcommerce_wp_post_create_error',
            'status_code' => 400
          )
        );
      }
    }
  }
}
