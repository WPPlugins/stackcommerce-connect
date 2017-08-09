<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}
?>

<div class="wrap">
	<h1><?php esc_html_e( SCWP_NAME ); ?> - Settings</h1>

  <?php
  if( isset( $_GET['settings-updated'] ) ) {
    add_settings_error(
      'stackcommerce_wp_messages',
      'stackcommerce_wp_message',
      __( 'Settings Saved', 'stackcommerce_wp' ),
      'updated'
    );
  }

  settings_errors( 'stackcommerce_wp_messages' );
  ?>

  <input type="hidden" id="stackcommerce_wp_endpoint" value="<?php echo site_url(); ?>/index.php?sc-api-version=<?php echo SCWP_API_VERSION; ?>&sc-api-route=posts" />

  <input type="hidden" id="stackcommerce_wp_cms_api_endpoint" value="<?php echo CMS_API_ENDPOINT; ?>" />

	<form method="post" class="stackcommerce-wp-form" id="stackcommerce-wp-form" action="options.php" autocomplete="off" data-stackcommerce-wp-status data-stackcommerce-wp-content-integration>

		<?php settings_fields( 'stackcommerce_wp' ); ?>

    <div class="stackcommerce-wp-section">
  		<?php do_settings_sections( 'stackcommerce_wp' ); ?>
    </div>

		<p class="submit">
			<input type="button" class="button-primary stackcommerce-wp-form-submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
		</p>
	</form>
</div>
