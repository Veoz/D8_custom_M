<?php


namespace Drupal\lendos\Controller;


class Remove {

  public function remove(){

    $remove_lendos = \Drupal::formBuilder()->getform('Drupal\lendos\Form\Remove');

    return array(
      '#theme' => 'lendos_remove_theme',
      '#remove_lendos' => $remove_lendos,
    );

  }

}
