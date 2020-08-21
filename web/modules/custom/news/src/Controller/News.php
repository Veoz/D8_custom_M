<?php

namespace Drupal\news\Controller;

use Drupal\Core\Controller\ControllerBase;

class News extends ControllerBase {

  public function add(){

    $addnews = \Drupal::formBuilder()->getform('Drupal\news\Form\Add');

    return array(
      '#theme' => 'news_add_theme',
      '#addnews' => $addnews,
    );

  }

}
