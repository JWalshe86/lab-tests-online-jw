<?php

namespace Drupal\custom_batch_anchor_content_updater\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\custom_global_glossary_anchor_removal\Queries\AnchorQueries;

/**
 * Form with examples on how to use batch api.
 */
class BatchAnchorUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_batch_anchor_content_updater';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $bundles = array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo('node'));

    // Default values.
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('The mass in-content link (anchor href) global node content updater.'),
      '#weight' => '0',
    ];

    $form['search_node'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Search Node (the page to replace within content links)'),
      '#description' => $this->t('The page which is linked in content which you want to find and update.'),
      '#selection_settings' => [
        'target_bundles' => $bundles,
      ],
      '#weight' => '1',
    ];

    $form['relative_replacement'] = [
      '#type' => 'hidden',
      '#value' => FALSE,
    ];

    // Override values.
    if (\Drupal::routeMatch()->getRouteName() !== 'mass-editing_404.form') {
      $form['include_related_content'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include Related Content?'),
        '#description' => $this->t('If checked, links within the "Related Content" section of nodes will be updated. However, since these links are actually entity references, the Search Node type and Replacement Node type must match in type. For example, if your Search Node is a Basic Page, and this box is checked, then Replacement Node must also be a Basic Page. This literally updates the entity reference, if you do not understand what this means, then you should not even submit this form and you should get clarification from a colleague as to what settings you should use.'),
        '#weight' => '0',
        '#default_value' => FALSE,
      ];
    }

    if (\Drupal::routeMatch()->getRouteName() === 'mass-editing_404.form') {
      $form['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('The mass in-content link (anchor href) global 404 node content updater. The intended use-case is to globally mass fix relative 404 links generally in the form /node/{id} where {id} is an integer. Most of the time you should use the /admin/mass-content-link-editing-standard UI instead (if you need to include Related Content, please also use the /admin/mass-content-link-editing-standard UI).'),
        '#weight' => '0',
      ];

      $form['search_node'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Relative Search Node Path'),
        '#description' => $this->t('A relative URL to find and replace in links. The relative URL cannot exist in the system as a page (node). If it does, consider using the /admin/mass-content-link-editing-standard UI instead.'),
        '#weight' => '1',
      ];

      $form['relative_replacement'] = [
        '#type' => 'hidden',
        '#value' => TRUE,
      ];

      $form['include_related_content'] = [
        '#type' => 'hidden',
        '#value' => FALSE,
      ];
    }

    $form['replacement_node'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Replacement Node (the page to use as the replacement within content links)'),
      '#description' => $this->t('The page you want links to update to use as their link value (anchor href value).'),
      '#selection_settings' => [
        'target_bundles' => $bundles,
      ],
      '#weight' => '2',
    ];

    $form['update_links'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update In-Content Page Links'),
      '#weight' => 3,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $isRelativeReplacement = $form_state->getValue('relative_replacement');
    $searchNode = trim($form_state->getValue('search_node'));
    $replacementNode = trim($form_state->getValue('replacement_node'));
    $nodePathCheck = preg_match("/^\/node.?\/.*[\w.-?#]+$/i", $searchNode);

    if ($isRelativeReplacement) {
      $regex = preg_match('/^\/.*[\w.-?#]+$/i', $searchNode);
      if (!$regex) {
        $form_state->setErrorByName('title', $this->t("The relative search node path must be a relative URL."));
        return FALSE;
      }
      if ($nodePathCheck) {
        $searchNodeAlias = \Drupal::service('path_alias.manager')->getAliasByPath($searchNode);
        if ($searchNode !== $searchNodeAlias) {
          $form_state->setErrorByName('title', $this->t("The path you provided as the relative URL for the Search Node is reserved by a Node in the system. Please use the /admin/mass-content-link-editing-standard UI to update links for this case."));
          return FALSE;
        }
      }
      else {
        $searchNodePath = \Drupal::service('path_alias.manager')->getPathByAlias($searchNode);
        if ($searchNode !== $searchNodePath) {
          $form_state->setErrorByName('title', $this->t("The path you provided as the relative URL for the Search Node is reserved by a Node in the system. Please use the /admin/mass-content-link-editing-standard UI to update links for this case."));
          return FALSE;
        }
      }
      return TRUE;
    }
    else {
      $includeRelatedContent = $form_state->getValue('include_related_content');
      if ($includeRelatedContent && Node::load($searchNode)->bundle() !== Node::load($replacementNode)->bundle()) {
        $form_state->setErrorByName('title', $this->t("Since you are including Related Content, the Search Node type must match the Replacement Node type. For example, Search Node could not be a Basic Page while Replacement Node is an Article. They must be the same type. If you do not inlcude Related Content, then types do not need to be the same."));
        return FALSE;
      }
      return TRUE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queryGenerator = new AnchorQueries();
    $allNodes = $queryGenerator->getAllNodes();
    $operations = [];

    $searchNodeId = $form_state->getValue('search_node');
    $replacementNodeId = $form_state->getValue('replacement_node');
    $isRelativeReplacement = $form_state->getValue('relative_replacement');
    $includeRelatedContent = $form_state->getValue('include_related_content');

    $allNodes = array_chunk($allNodes, 10, TRUE);
    foreach ($allNodes as $index => $nodes) {
      $operations[] = [
        '\Drupal\custom_batch_anchor_content_updater\Batch\BatchService::createDataset',
        [
          $nodes,
          $searchNodeId,
          $replacementNodeId,
          $isRelativeReplacement,
          $includeRelatedContent,
        ],
      ];
    }

    $batch = [
      'title' => t('Updating @num node(s)', ['@num' => count($operations)]),
      'operations' => $operations,
      'finished' => '\Drupal\custom_batch_anchor_content_updater\Batch\BatchService::processNewDataSet',
    ];

    if (file_exists('/tmp/custom_batch_anchor_content_updater-lock.txt') && time() - filemtime('/tmp/custom_batch_anchor_content_updater-lock.txt') < 1200) {
      // The process may run for 20 minutes.
      \Drupal::messenger()->addMessage('Another user is currently running this process! You must try again later.');
    }
    else {

      if (file_exists('/tmp/custom_batch_anchor_content_updater-lock.txt')) {
        // Delete Orphaned file and recreate for this batch run.
        unlink('/tmp/custom_batch_anchor_content_updater-lock.txt');
      }

      $lockFile = fopen("/tmp/custom_batch_anchor_content_updater-lock.txt", "w") or die("Unable to open file!");

      // Duplicates are expected and needed in $results.
      $txt = 'running';
      fwrite($lockFile, $txt);
      fclose($lockFile);
      batch_set($batch);
    }
  }

}
