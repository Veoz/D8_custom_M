<?php


namespace Drupal\hello\Controller;




use Drupal\Console\Bootstrap\Drupal;

class SingleNews {


  public function get_news($id = 0){

    $base_url = $this -> base_url();
    $menu = \Drupal\hello\Controller\Menu::menu_data('home');

    $news = [];
    $query = \Drupal::database()->query("SELECT `name`, `description`, `date_create` FROM `a_news` WHERE `id` = :id",[':id' => $id]);
    $result = $query -> fetchAll();

    foreach ($result as $row) {
      $news = ['title' => $row->name,'content' => $row->description, 'date' => $row->date_create,];
    }



    return  array(
      '#theme' => 's_news_theme',
      '#news' => $news,
      '#base_url' => $base_url,
      '#menu' => $menu,
    );
  }
  private function base_url(){
    global $base_url;
    return $base_url;
  }
}
