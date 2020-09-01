<?php


namespace Drupal\lendos\Controller;


class Remove {

  public function remove($id = 0){

    $remove_lendos = \Drupal::formBuilder()->getform('Drupal\lendos\Form\Remove', $id);

    return array(
      '#theme' => 'lendos_remove_theme',
      '#remove_lendos' => $remove_lendos,
    );

  }

}
