<?php


namespace Drupal\lendos\Controller;


class Edit {

  public function edit(){
    $edit_lendos = \Drupal::formBuilder()->getform('Drupal\lendos\Form\Edit');

    return array(
      '#theme' => 'lendos_edit_theme',
      '#edit_lendos' => $edit_lendos,
    );
  }

}
