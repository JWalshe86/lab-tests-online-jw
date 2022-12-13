<?php

namespace Drupal\external_link\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a External Link revision.
 *
 * @ingroup external_link
 */
class ExternalLinkRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The External Link revision.
   *
   * @var \Drupal\external_link\Entity\ExternalLinkInterface
   */
  protected $revision;

  /**
   * The External Link storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $externalLinkStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new ExternalLinkRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->externalLinkStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // $entity_manager = $container->get('entity.manager');
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('external_link'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_link_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.external_link.version_history', [
      'external_link' => $this->revision->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $external_link_revision = NULL) {
    $this->revision = $this->externalLinkStorage->loadRevision($external_link_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->externalLinkStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('External Link: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    \Drupal::messenger()->addStatus(t('Revision from %revision-date of External Link %title has been deleted.', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect(
      'entity.external_link.canonical',
       ['external_link' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {external_link_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.external_link.version_history',
         ['external_link' => $this->revision->id()]
      );
    }
  }

}
