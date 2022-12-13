<?php

namespace Drupal\revision_date;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
 *
 * @internal
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(DateFormatterInterface $dateFormatter) {
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * Act on entities being assembled before rendering.
   *
   * @see hook_entity_view()
   * @see EntityFieldManagerInterface::getExtraFields()
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {

    /** @var \Drupal\Core\Entity\RevisionLogInterface $entity */
    if (!$entity instanceof RevisionLogInterface) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (isset($entity->in_preview) && $entity->in_preview) {
      return;
    }

    // If the component is not defined for this display, we have nothing to do.
    if (!$display->getComponent('revision_date')) {
      return;
    }

    $build['revision_date'] = [
      '#theme' => 'revision_date',
      '#date' => $this->dateFormatter->format($entity->getRevisionCreationTime(), 'month_day_year'),
      '#timezone' => $entity->getRevisionCreationTime(),
    ];
  }

}
