<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}

/**
 * Uninstall Hook
 *
 * @since      1.0.4
 * @package    StackCommerce_WP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  exit();
}

$account_id = get_option( 'stackcommerce_wp_account_id' );
$secret = get_option( 'stackcommerce_wp_secret' );
$api_endpoint = CMS_API_ENDPOINT . '/api/wordpress/?id=' . $account_id . '&secret=' . $secret;

$data = array(
  'data' => [
    'type' => 'partner_wordpress_settings',
    'id'   => $account_id,
    'attributes' => [
      'installed' => false,
    ],
  ],
);

$data = json_encode($data);

$options = array(
  CURLOPT_CUSTOMREQUEST  => 'PUT',
  CURLOPT_URL            => $api_endpoint,
  CURLOPT_POSTFIELDS     => $data,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
);

$ch = curl_init();
curl_setopt_array( $ch, $options );

curl_exec( $ch );
curl_close( $ch );

$__options = array(
  'stackcommerce_wp_account_id',
  'stackcommerce_wp_secret',
  'stackcommerce_wp_connection_status',
  'stackcommerce_wp_content_integration',
  'stackcommerce_wp_author',
  'stackcommerce_wp_post_status',
  'stackcommerce_wp_categories',
  'stackcommerce_wp_tags',
  'stackcommerce_wp_featured_image'
);

foreach( $__options as $option_name ) {
  delete_option( $option_name );
  delete_site_option( $option_name );
}

unset( $__options );
