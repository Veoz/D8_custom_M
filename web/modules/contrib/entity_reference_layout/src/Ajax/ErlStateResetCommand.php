<?php

namespace Drupal\entity_reference_layout\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ErlState.
 */
class ErlStateResetCommand implements CommandInterface {

  /**
   * The wrapper DOM id.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructs an ErlState instance.
   */
  public function __construct($id) {
    $this->id = $id;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'resetErlState',
      'data' => [
        "id" => $this->id,
      ],
    ];
  }

}
