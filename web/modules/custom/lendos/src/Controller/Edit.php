<?php


namespace Drupal\lendos\Controller;


class Edit {

  public function edit($id = 0){
    $edit_lendos = \Drupal::formBuilder()->getform('Drupal\lendos\Form\Edit', $id);

    return array(
      '#theme' => 'lendos_edit_theme',
      '#edit_lendos' => $edit_lendos,

    );
  }

}
