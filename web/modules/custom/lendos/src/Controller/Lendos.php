<?php

namespace Drupal\lendos\Controller;

use Drupal\Core\Controller\ControllerBase;

class Lendos extends ControllerBase {


  public function get_all() {

    global $base_url;

    $lendos = [];


    $query = \Drupal::database()->select('a_lendos', 'n');
    $query->fields('n', ['name', 'mail', 'tell', 'text', 'img','date_create','avatar',]);
    $result = $query->execute()->fetchAll();


    foreach ($result as $row) {
      array_push($lendos, [
        'name' => $row->name,
        'text' => $row->text,
        'tell' => $row->tell,
        'mail' => $row->mail,
        'img'  => $row->img,
        'date' => $row->date_create,
        'avatar' => $row-> avatar,
      ]);
    }

    $data        = [
      'title'  => 'LENDOS',
      'lendos' => $lendos,
    ];
    $add_lendos = \Drupal::formBuilder()->getform('Drupal\lendos\Form\Add');

    return [
      '#theme'       => 'lendos_theme',
      '#data'        => $data,
      '#base_url'    => $base_url,
      '#add_lendos' => $add_lendos,
    ];

  }
}


