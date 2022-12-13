<?php

namespace Drupal\aacc_feeds;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Feed entity.
 *
 * @ingroup aacc_feeds
 * @category AACC_Feeds
 * @package AACC
 * @author jelmore@unleashed-technologies.com <jelmore@unleashed-technologies.com>
 * @license https://unleashed-technologies.com Unleashed-Technologies.com
 * @link https://labtestsonline.com
 */
interface FeedInterface extends
    ContentEntityInterface,
    EntityOwnerInterface,
    EntityChangedInterface {

}
