<?php

/**
 * @file
 * Post-update functions for AACC Stakeholders.
 */

/**
 * Change the 'last run' state value from month to timestamp.
 */
function aacc_stakeholders_post_update_1() {
  $last_run = \Drupal::state()->get('aacc_stakeholders.last_run');
  if (in_array($last_run, [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ])) {
    $new_last_run = strtotime('last day of ' . $last_run . ' noon');
    \Drupal::state()->set('aacc_stakeholders.last_run', $new_last_run);
  }
}
