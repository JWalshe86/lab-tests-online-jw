<?php

namespace Drupal\aacc_ascls;

use Drupal\Core\Queue\RequeueException;

/**
 * Class TransmitQuestion.
 *
 * Service class to transmit data to ASCLS endpoint.
 *
 * @package Drupal\aacc_ascls
 */
class TransmitQuestion {

  /**
   * The transmission status returned from ASCLS.
   *
   * @var \Drupal\aacc_ascls\TransmitQuestion
   */
  private $transmissionData;

  /**
   * Worker function to communicate with ASCLS.
   *
   * @param array $data
   *   The webform submission data.
   * @param int $sid
   *   The webform submission id.
   */
  public function sendQuestion(array $data, $sid) {
    // Get environment URL.
    $endpoint = $this->asclsEndpoint();

    // Clone this array, since we'll need it later.
    $data2 = $data;
    // ASCLS requires both the email and email confirmation values
    // (email1 and email2).
    // Webform provides an email confirm element that displays
    // two different fields, and provides server side validation
    // that they match, but only saves the primary email
    // in the data. Since it validates that they match before it
    // gets here, we can assume that they match, and just copy the email1
    // value into email2.
    $data2['email2'] = $data['email1'];
    // ASCLS doesn't need the terms_of_service checkbox value,
    // so we need to remove that.
    unset($data2['terms_of_service']);
    // Also unset the hidden fields.
    unset($data2['transmit_count']);
    unset($data2['transmit_status']);
    unset($data2['transmit_message']);
    // Guzzle requires a form_params key.
    $form_params = [
      'form_params' => $data2,
    ];

    // Make call to ASCLS endpoint.
    $client = \Drupal::httpClient();
    try {
      $response = $client->post($endpoint, $form_params);
      $response_body = json_decode($response->getBody()->getContents());
      $response_message = $response_body->message;
    }
    catch (RequeueException $e) {
      watchdog_exception('aacc_ascls', $e->getMessage());
    }

    if ($response_body->status) {
      // Successful transmission.
      // Possible values for transmit status are:
      // successful
      // failed
      // pending
      // In this case, it was successful.
      $this->transmissionData['transmit_status'] = 'successful';
      // Convert to integer just to make sure.
      $this->transmissionData['transmit_count'] = (int) $data['transmit_count'];
      $this->transmissionData['transmit_count']++;

      // Override message in case this is the second time
      // through for this submission.
      $this->transmissionData['transmit_message'] = $response_message;

      return $this->transmissionData;
    }
    else {
      // Submission failed.
      // Record message that update failed.
      $this->transmissionData['transmit_status'] = 'failed';
      // Convert to integer just to make sure.
      $this->transmissionData['transmit_count'] = (int) $data['transmit_count'];
      $this->transmissionData['transmit_count']++;
      $this->transmissionData['transmit_message'] = $response_message;

      // Since it failed, we need to add the item to a queue
      // to process again when cron runs.
      $queue = \Drupal::queue('transmit_question_queue');
      // We only store the sid, since we will load the data
      // when we process it again.
      $queue->createItem(['sid' => $sid]);

      // Return the data.
      return $this->transmissionData;
    }
  }

  /**
   * Get the appropriate ASCLS endpoint.
   */
  public function asclsEndpoint() {
    $config = \Drupal::config('aacc_ascls.api');
    $var = ($config->get('use_dev_endpoints') === 'FALSE') ? 'endpoint_prod' : 'endpoint_dev';
    return $config->get($var);
  }

}
