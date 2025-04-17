<?php

declare(strict_types=1);

namespace Drupal\demo\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a who block.
 */
#[Block(
  id: 'demo_who',
  admin_label: new TranslatableMarkup('Who'),
  category: new TranslatableMarkup('Custom'),
)]
final class WhoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $logged_in_user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load($logged_in_user->id());
    $user_name = $user->getDisplayName();
    $build['content'] = [
      '#markup' => $this->t('Hello <strong>@name!', ['@name' => $user_name]),
      '#prefix' => '<div class="who-block">',
      '#suffix' => '</div>',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'demo/whoami',
        ],
      ],
    ];
    return $build;
  }

}
