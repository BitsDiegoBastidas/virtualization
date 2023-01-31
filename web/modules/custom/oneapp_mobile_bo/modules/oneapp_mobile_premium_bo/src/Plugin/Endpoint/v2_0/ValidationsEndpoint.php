<?php

namespace Drupal\oneapp_mobile_premium_bo\Plugin\Endpoint\v2_0;

use Drupal\oneapp_endpoints\EndpointBase;

/**
 * Provides a 'ValidationsEndpoint' entity.
 *
 * @Endpoint(
 * id = "oneapp_mobile_premium_v2_0_validations_endpoint",
 * admin_label = @Translation("Mobile premium validations v2.0"),
 *  defaults = {
 *    "endpoint" = "https://[endpoint:environment_prefix].api.tigo.com/v1/tigo/[endpoint:country_iso]/validations/mobile/subscribers/{id}/addons",
 *    "method" = "POST",
 *    "timeout" = 60,
 *  },
 * )
 */
class ValidationsEndpoint extends EndpointBase {

}
