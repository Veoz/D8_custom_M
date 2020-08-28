<?php

namespace Drupal\lendos\Controller;

use Drupal\Core\Controller\ControllerBase;

class Lendos extends ControllerBase {


  public function get_all() {

    global $base_url;

    $lendos = [];


    $query = \Drupal::database()->select('a_lendos', 'n');
    $query->fields('n', ['name', 'mail', 'tell', 'text', 'img']);
    $result = $query->execute()->fetchAll();


    foreach ($result as $row) {
      array_push($lendos, [
        'name' => $row->name,
        'text' => $row->text,
        'tell' => $row->tell,
        'mail' => $row->mail,
        'img'  => $row->img,
      ]);
    }

    $data        = [
      'title'  => 'LENDOS',
      'lendos' => $lendos,
    ];
    $edit_lendos = \Drupal::formBuilder()->getform('Drupal\lendos\Form\Add');

    return [
      '#theme'       => 'lendos_theme',
      '#data'        => $data,
      '#base_url'    => $base_url,
      '#edit_lendos' => $edit_lendos,
    ];

  }
}


