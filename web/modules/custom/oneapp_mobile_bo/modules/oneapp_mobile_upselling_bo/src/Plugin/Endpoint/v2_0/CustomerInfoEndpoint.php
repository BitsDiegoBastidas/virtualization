<?php

namespace Drupal\oneapp_mobile_upselling_bo\Plugin\Endpoint\v2_0;

use Drupal\oneapp_endpoints\EndpointBase;

/**
 * Provides a 'CustomerInfoEndpoint' entity.
 *
 * @Endpoint(
 * id = "oneapp_mobile_upselling_v2_0_customer_info_endpoint",
 * admin_label = @Translation("Customer Info by msisdn"),
 *  defaults = {
 *    "endpoint" = "http://[endpoint:environment_prefix].api.tigo.com/v3/tigo/b2b/[endpoint:country_iso]/crm/MSISDN/{msisdn}/customerInfo",
 *    "method" = "GET",
 *    "timeout" = 60,
 *  },
 * )
 */
class CustomerInfoEndpoint extends EndpointBase {}
