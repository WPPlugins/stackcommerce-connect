<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}

/**
 * Uninstall Hooks
 *
 * @since     1.0.0
 * @package   StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Maintenance {

  /**
   * Perform activation tasks
   *
   * @since    1.0.0
   */
  public function activation() {
    global $pagenow;
    $connection_status = get_option( 'stackcommerce_wp_connection_status' );

		if( $pagenow === 'plugins.php' && $connection_status !== 'connected' ) {
      add_action( 'admin_notices', array( $this, 'notice' ) );
    }
  }

  /**
   * Perform deactivation tasks
   *
   * @since    1.0.0
   */
  public function deactivate() {
    if( current_user_can( 'activate_plugins' ) ) {
      self::notify();
      self::disconnect();

      flush_rewrite_rules();
    } else {
      return;
    }
  }

  /**
   * Perform tasks on plugin activation
   *
   * @since    1.0.0
   */
  protected function setup() {
    add_action( 'admin_notices', array( $this, 'activate_notice' ) );
  }

  /**
   * Trigger a success activation notice
   *
   * @since    1.0.0
   */
  public function notice() {
  	require_once( dirname( dirname( __FILE__ ) ) . '/views/stackcommerce-wp-activate.php' );
  }

  /**
   * Notify API to disconnect
   *
   * @since    1.0.0
   */
  protected function notify() {
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
  }

  /**
   * Clean up fields created by the plugin
   *
   * @since    1.0.0
   */
  protected function disconnect() {
    return update_option( 'stackcommerce_wp_connection_status', 'disconnected' );
  }
}
