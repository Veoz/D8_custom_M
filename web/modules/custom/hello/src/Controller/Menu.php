<?php


namespace Drupal\hello\Controller;


class Menu {

  public static function menu_data($active_page=null){
    $menu = array(
      'home' =>
        array(
          'url' => 'welcome',
          'img' => 'contact.swg',
          'style' => 'contact_swg',
          'name' => 'Головна',
          'active' => $active_page === 'home' ? 'active' : null,
        ),
      'about' =>
        array(
          'url' => 'welcome',
          'img' => 'contact.swg',
          'style' => 'contact_swg',
          'name' => 'Про нас',
          'active' => $active_page === 'home' ? 'active' : null,
        ),
    );
    return $menu;

  }


}
