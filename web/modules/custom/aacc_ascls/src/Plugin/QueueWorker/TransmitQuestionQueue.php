<?php

namespace Drupal\aacc_ascls\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class TransmitQuestionQueue.
 *
 * Transmits previously failed submissions to ASCLS.
 *
 * @QueueWorker(
 *   id = "transmit_question_queue",
 *   title = @Translation("Transmits submissions that previously failed."),
 *   cron = {"time" = 120}
 * )
 *
 * @package Drupal\acc_ascls\Plugin\QueueWorker
 */
class TransmitQuestionQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $queue = \Drupal::queue('transmit_question_queue');

    // Load the submission.
    $submission = WebformSubmission::load($data['sid']);

    if ($submission) {
      $submission_data = $submission->getData();

      // Call the service to transmit.
      $service = \Drupal::service('aacc_ascls.transmit_question');
      $response_values = $service->sendQuestion($submission_data, $data['sid']);

      // Write the data from the response back to the submission.
      foreach ($response_values as $key => $value) {
        $submission_data[$key] = $value;
      }

      // Save the data to the submission.
      $submission->setData($submission_data);

      // Save the submission.
      $submission->save();
    }
  }

}
