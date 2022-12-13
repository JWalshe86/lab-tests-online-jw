<?php

namespace Drupal\aacc_ascls\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "ask_a_scientist_form_handler",
 *   label = @Translation("Ask a Laboratory Scientist Form Handler"),
 *   category = @Translation("ASCLS"),
 *   description = @Translation("A form handler to transmit data to ASCLS from the Ask A Laboratory Scientist webform"),
 *   cardinality = Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class AskAScientistFormHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Get the webform submission ID.
    $webform_submission->setSticky(!$webform_submission->isSticky())->save();
    $sid = $webform_submission->id();

    // Get form data.
    $data = $webform_submission->getData();

    // Call the service here.
    $service = \Drupal::service('aacc_ascls.transmit_question');
    $response_values = $service->sendQuestion($data, $sid);

    // Write the data from the response back to the submission.
    foreach ($response_values as $key => $value) {
      $data[$key] = $value;
    }

    // Save the data to the submission.
    $webform_submission->setData($data);
  }

}
