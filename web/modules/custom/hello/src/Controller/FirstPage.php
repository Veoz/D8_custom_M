<?php

namespace Drupal\hello\Controller;
//http://127.0.0.1/drupal8/web/admin/flush?token=kDDz_sLiUBnFqi7CFEDbV3U1DaNSZAHDtsOM89Ef7BM
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines HelloController class.
 */
class FirstPage extends ControllerBase {

  public function content()  {

    global $base_url;

    $menu = \Drupal\hello\Controller\Menu::menu_data('home');

    $news = array();
    $query = \Drupal::database()->query("SELECT `id`, `name`, `description_small` FROM `a_news` ORDER BY `date_create`DESC");
    $result = $query -> fetchAll();

    foreach ($result as $row) {
      array_push($news, ['id' => $row->id,'title' => $row->name,'content' => $row->description_small,]);
    }

    $data = array(
      'title' => 'Welcome to news stream',
      'window' =>$news
      );

      return  array(
        '#theme' => 'hello_theme',
        '#data' => $data,
        '#base_url' => $base_url,
        '#menu' => $menu,
      );
//    return [
//      '#type' => 'markup',
//      '#markup' => $this->t('Hello, World!'),
//    ];
  }

}
