<?php
if( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}

/**
 * Register Settings API and Page
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Settings extends StackCommerce_WP_Module {

  const REQUIRED_CAPABILITY = 'administrator';

  /**
   * Get WordPress authors
   *
   * @since    1.0.0
   */
  private function get_authors() {
    $authors_filter = array(
      'role__in' => array( 'Administrator', 'Editor', 'Author' )
    );

    $authors_query = new WP_User_Query( $authors_filter );

    $authors_return = array();

    foreach( $authors_query->results as $author ) {
      $authors_return[$author->ID] = $author->display_name;
  	}

    return $authors_return;
  }

  /**
   * Get WordPress categories
   *
   * @since    1.1.0
   */
  private function get_categories() {
    $categories_filter = array (
      'hide_empty' => false
    );

    $categories = get_categories( $categories_filter );
    $categories_return = array();

    foreach( $categories as $category ) {
      $categories_return[$category->slug] = $category->name;
    }

    return $categories_return;
  }

  /**
   * Get WordPress tags
   *
   * @since    1.0.0
   */
  private function get_tags() {
    $tags_filter = array (
      'hide_empty' => false
    );

    $tags = get_tags( $tags_filter );
    $tags_return = array();

    foreach( $tags as $tag ) {
      $tags_return[$tag->slug] = $tag->name;
    }

    return $tags_return;
  }

  /**
   * Register Settings API, its sections and fields
   *
   * @since    1.0.0
   */
  public function register_api() {
    register_setting( 'stackcommerce_wp', 'stackcommerce_wp' );

    add_settings_section(
      'stackcommerce_wp_section_one',
      '',
      array( $this, 'section_callback' ),
      'stackcommerce_wp'
    );

    add_settings_section(
      'stackcommerce_wp_section_two',
      '',
      array( $this, 'section_callback' ),
      'stackcommerce_wp'
    );

    add_settings_section(
      'stackcommerce_wp_section_three',
      '',
      array( $this, 'section_callback' ),
      'stackcommerce_wp'
    );

    add_settings_section(
      'stackcommerce_wp_section_four',
      '',
      array( $this, 'section_callback' ),
      'stackcommerce_wp'
    );

    $fields = array(
    	array(
    		'uid'          => 'stackcommerce_wp_account_id',
    		'label'        => 'Account ID',
    		'section'      => 'stackcommerce_wp_section_one',
    		'type'         => 'text',
        'default'      => '',
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => '',
    	),
    	array(
    		'uid'          => 'stackcommerce_wp_secret',
    		'label'        => 'Secret Key',
    		'section'      => 'stackcommerce_wp_section_one',
    		'type'         => 'password',
        'default'      => '',
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => '',
      ),
      array(
    		'uid'     => 'stackcommerce_wp_connection_status',
    		'label'   => 'Connection Status',
    		'section' => 'stackcommerce_wp_section_two',
    		'type'    => 'hidden',
        'default'      => '',
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => '',
    	),
      array(
    		'uid'     => 'stackcommerce_wp_content_integration',
    		'label'   => 'Content Integration',
    		'section' => 'stackcommerce_wp_section_three',
    		'type'    => 'radio',
        'options' => array(
          'false' => 'Off',
          'true' => 'On',
        ),
        'default'      => array('false'),
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => ''
    	),
      array(
    		'uid'     => 'stackcommerce_wp_author',
    		'label'   => 'Author',
    		'section' => 'stackcommerce_wp_section_four',
    		'type'    => 'select',
    		'options' => $this->get_authors(),
        'default'      => array(),
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => '',
    	),
    	array(
    		'uid'     => 'stackcommerce_wp_post_status',
    		'label'   => 'Post Status',
    		'section' => 'stackcommerce_wp_section_four',
    		'type'    => 'select',
    		'options' => array(
    			'2' => 'Schedule',
    			'0' => 'Draft',
    			'1' => 'Pending',
    		),
        'default'      => array(),
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => 'This will be the status of the post when we send it. The schedule option allows us to identify the date and time the post will go live. The draft and pending options will require you to manually schedule the posts.',
    	),
      array(
    		'uid'     => 'stackcommerce_wp_categories',
    		'label'   => 'Categories',
    		'section' => 'stackcommerce_wp_section_four',
    		'type'    => 'multiselect',
    		'options' => $this->get_categories(),
        'default'      => array(),
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => 'Categories entered in this field will be sent with <b>all</b> posts. Only enter categories you want to be applied on everything.<br />e.g. StackCommerce, Sponsored, Partners',
    	),
      array(
    		'uid'     => 'stackcommerce_wp_tags',
    		'label'   => 'Tags',
    		'section' => 'stackcommerce_wp_section_four',
    		'type'    => 'multiselect',
    		'options' => $this->get_tags(),
        'default'      => array(),
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => 'Tags entered in this field will be sent with <b>all</b> posts. Only enter tags you want to be applied on everything.<br />e.g. stackcommerce, sponsored',
    	),
      array(
    		'uid'     => 'stackcommerce_wp_featured_image',
    		'label'   => 'Featured Image Settings',
    		'section' => 'stackcommerce_wp_section_four',
    		'type'    => 'select',
        'options' => array(
          'featured_image_only' => 'Set Featured Image',
          'featured_image_plus_body' => 'Set Featured Image, plus include this image in article body',
          'no_featured_image' => 'Do not set a Featured Image',
        ),
        'default'      => array(),
        'placeholder'  => '',
        'helper'       => '',
        'supplimental' => ''
    	)
    );

  	foreach( $fields as $field ) {
      add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'stackcommerce_wp', $field['section'], $field );

      register_setting( 'stackcommerce_wp', $field['uid'] );

      if( $field['uid'] === 'stackcommerce_wp_connection_status' && get_option( $field['uid'] ) === false ) {
        update_option( $field['uid'], 'disconnected' );
      }
  	}
  }

  /**
   * Sections Callbacks
   *
   * @since    1.0.0
   */
  public function section_callback( $arguments ) {
    	switch( $arguments['id'] ) {
    		case 'stackcommerce_wp_section_two':
    			echo '<p>The Account ID and Secret Key will be provided during initial setup. This allows you to securely connect to our system. The connection status below will indicate when a successful connection is made. These values should not change after a successful connection.</p>';
    			break;
        case 'stackcommerce_wp_section_three':
          echo '<h2>Content Settings</h2>';
    			echo '<p>' . SCWP_NAME . ' allows posts to be scheduled directly in your WordPress CMS when you utilize our Brand Studio content service. Articles can be syndicated in draft, pending, or scheduled status. Turn ON content integration below if you are currently using this service.</p>';
    			break;
    	}
    }

  /**
   * Fields Callbacks
   *
   * @since    1.0.0
   */
  public function field_callback( $arguments ) {
      $value = get_option( $arguments['uid'] );

      if( !$value ) {
        $value = $arguments['default'];
      }

      switch( $arguments['type'] ) {
        case 'text':
        case 'password':
          printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" autocomplete="off" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
          break;
        case 'select':
        case 'multiselect':
          if( is_array( $arguments['options'] ) ) {
            $attributes = '';
            $options_markup = '';

            foreach( $arguments['options'] as $key => $label ) {
              $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( @$value[ array_search( $key, $value, true ) ], $key, false ), $label );
            }

            if( $arguments['type'] === 'multiselect' ) {
              $attributes = ' class="stackcommerce-wp-form-select2" multiple="multiple" ';
            }

            printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
          }
          break;
          case 'radio':
          case 'checkbox':
              if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                  $options_markup = '';
                  $iterator = 0;
                  foreach( $arguments['options'] as $key => $label ){
                      $iterator++;
                      $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label>', $arguments['uid'], $arguments['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $iterator );
                  }
                  printf( '<fieldset>%s</fieldset>', $options_markup );
              }
              break;
        case 'hidden':
          print( '<div class="stackcommerce-wp-status-disconnected"> <span class="stackcommerce-wp-status-icon stackcommerce-wp-status-icon-disconnected"></span> <p class="stackcommerce-wp-status">Disconnected</p> </div>' );

          print( '<div class="stackcommerce-wp-status-connected"> <span class="stackcommerce-wp-status-icon stackcommerce-wp-status-icon-connected"></span> <p class="stackcommerce-wp-status">Connected</p> </div>' );

          print( '<div class="stackcommerce-wp-status-connecting"> <span class="stackcommerce-wp-status-icon stackcommerce-wp-status-icon-connecting"></span> <p class="stackcommerce-wp-status">Connecting</p> </div>' );

          printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
          break;
      }

      if( $helper = $arguments['helper'] ) {
        printf( '<span class="helper"> %s</span>', $helper );
      }

      if( $supplimental = $arguments['supplimental'] ) {
        printf( '<p class="description">%s</p>', $supplimental );
      }
  }

  /**
   * Register Settings Menu Item
   *
   * @since    1.0.0
   */
  public function register_menu() {
    add_menu_page(
      'StackCommerce',
      'StackCommerce',
      'disable',
      'stackcommerce_wp_page',
      '',
      'none',
      '81'
    );

    add_submenu_page(
      'stackcommerce_wp_page',
      'General Settings',
      'General Settings',
      self::REQUIRED_CAPABILITY,
      'stackcommerce_wp_page_general_settings',
      __CLASS__ . '::page'
    );
  }

  /**
   * Settings Page Template
   *
   * @since    1.0.0
   */
  public static function page() {
    if( current_user_can( self::REQUIRED_CAPABILITY ) ) {
      echo self::render_template( 'stackcommerce-wp-page-settings.php' );
    } else {
      wp_die( 'Access denied.' );
    }
  }
}
