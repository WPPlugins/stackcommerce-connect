'use strict';

;(function(window, document, $) {
  var endpoint = $('#stackcommerce_wp_cms_api_endpoint').val();

  $(document).ready(function() {
    scwp_check();
    scwp_init_categories_search();
    scwp_init_tags_search();

    $('.stackcommerce-wp-form-submit').click(scwp_validate);
    $('form.stackcommerce-wp-form').keypress(scwp_keycheck);
    $('input[name="stackcommerce_wp_content_integration[]"]').change(scwp_check);
  });

  function scwp_validate() {
    var fields          = scwp_generate(),
        accountId       = $('#stackcommerce_wp_account_id').val(),
        sharedSecretKey = $('#stackcommerce_wp_secret').val(),
        $form           = document.getElementById('stackcommerce-wp-form');

    scwp_status('connecting');

    $.ajax({
      method: 'PUT',
      url: endpoint + '/api/wordpress/?id=' + accountId + '&secret=' + sharedSecretKey,
      processData: false,
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify(fields),
    })
    .done(function(response) {
      scwp_status('connected');
      $form.submit();
    })
    .fail(function(error) {
      scwp_status('disconnected');
    });
  }

  function scwp_generate() {
    var account_id    = ($('#stackcommerce_wp_account_id').val()) ? $('#stackcommerce_wp_account_id').val() : '',
        wordpress_url = ($('#stackcommerce_wp_endpoint').val()) ? $('#stackcommerce_wp_endpoint').val() : '',
        author_id     = ($('#stackcommerce_wp_author').val()) ? $('#stackcommerce_wp_author').val() : '',
        post_status   = ($('#stackcommerce_wp_post_status').val()) ? $('#stackcommerce_wp_post_status').val() : '',
        tags          = ($('#stackcommerce_wp_tags').val()) ? $('#stackcommerce_wp_tags').val() : [],
        categories    = ($('#stackcommerce_wp_categories').val()) ? $('#stackcommerce_wp_categories').val() : [],
        featured_image = $('#stackcommerce_wp_featured_image').val();

    var data = {
      'data': {
        'type': 'partner_wordpress_settings',
        'id': account_id,
        'attributes': {
          'installed': true,
          'wordpress_url': wordpress_url,
          'author_id': author_id,
          'post_status': post_status,
          'categories': categories,
          'tags': tags,
          'featured_image': featured_image,
        },
      }
    };

    return data;
  }

  function scwp_status(status) {
    $('#stackcommerce_wp_connection_status').val(status);
    scwp_check();
  }

  function scwp_check() {
    var status = $('#stackcommerce_wp_connection_status').val(),
        form   = $('form.stackcommerce-wp-form'),
        submit = $('.stackcommerce-wp-form-submit'),
        content_integration = ($('input[name="stackcommerce_wp_content_integration[]"]:checked').val() == 'true') ? true : false;

    form.attr('data-stackcommerce-wp-status', status);
    form.attr('data-stackcommerce-wp-content-integration', content_integration);
    submit.attr('disabled', (status == 'connecting' ? true : false));
  }

  function scwp_keycheck(e) {
    if(e.which == 13) {
      scwp_validate();
      return false;
    }
  }

  function scwp_search(options) {
    $(options.selector).select2({
      ajax: {
        type: 'POST',
        url: ajaxurl,
        dataType: 'json',
        delay: 250,
        data: function(params) {
          return {
            action: 'sc_api_search',
            taxonomy: options.taxonomy,
            q: params.term,
          }
        },
        processResults: function(data, params) {
          var results = options.results.split('.').reduce(function(_data, key) {
            return _data[key];
          }, data);

          return {
            results: results
          }
        },
        cache: true
      },
      escapeMarkup: function(markup) { return markup; },
      minimumInputLength: 3,
    });
  }

  function scwp_init_categories_search() {
    var options = {
      selector: '#stackcommerce_wp_categories',
      taxonomy: 'categories',
      results:  'data.categories'
    }

    scwp_search(options);
  }

  function scwp_init_tags_search() {
    var options = {
      selector: '#stackcommerce_wp_tags',
      taxonomy: 'tags',
      results:  'data.tags'
    }

    scwp_search(options);
  }
})(window, document, jQuery);
