<?php


namespace Drupal\about\Controller;


class About {

  public function get_about(){

    $menu = \Drupal\hello\Controller\Menu::menu_data('home');
    global $base_url;

    $about = [];
//    $query = \Drupal::database()->query("SELECT `slogan`, `text`, `tell`, `mail`, `img` FROM `a_about`");
//    $result = $query -> fetchAll();
//
//    foreach ($result as $row) {
//      array_push($about, ['slogan' => $row->slogan,'text' => $row->text,'tell' => $row->tell, 'mail' => $row->mail,'img' => $row->img,]);
//    }

    $data = array(
      'title' => 'Welcome to news stream',
      'about' =>$about
    );

    return  array(
      '#theme' => 'about_theme',
      '#data' => $data,
      '#base_url' => $base_url,
      '#menu' => $menu,
    );


  }

}
