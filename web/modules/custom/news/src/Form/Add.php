<?php

namespace Drupal\news\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;



class Add extends FormBase{

  public function getFormId() {
   return 'addnews';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
   $form['date_create'] =[
     '#type' => 'textfield',
     '#title' => 'Дата створення',
     '#default_value' => date('d.m.Y',time()),
     '#required' => TRUE ,
   ];
   $form['name'] = [
     '#type' => 'textfield',
     '#title' => 'Наіменування',
     '#required' => TRUE,
   ];
   $form['description_small'] = [
     '#type' => 'text_format',
     '#format' => 'full_html',
     '#title' => 'Опис Анонсу',
     '#attributes' => [
     ],
     '#required' => TRUE,
     '#cols' => 60,
     '#resizable' => TRUE,
     '#rows' => 13,
   ];
   $form['description'] =[
     '#type' => 'text_format',
     '#format' => 'full_html',
     '#title' => 'Опис',
     '#attributes' => [
     ],
     '#required' => TRUE,
     '#cols' => 60,
     '#resizable' => TRUE,
     '#rows' => 13,
   ];

    $form['my_file'] = array(
      '#type' => 'managed_file',
      '#name' => 'my_file',
      '#title' => t('Картинка'),
      '#size' => 20,
      '#description' => t('Виберіть файл з розширенням jpg, jpeg, png или gif'),
      '#upload_location' => 'public://my_files/'
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => 'Зберегти',
    ];


   return $form;


  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
//    $validators = array(
//      'file_validate_is_image' => array(), // Проверка, действительно ли файл является изображением
//      'file_validate_extensions' => array('png gif jpg jpeg'), // Проверка на расширения
//      'file_validate_size' => array(1 * 1024 * 1024), // Проверка на размер файла (максимум 1mb)
//
//    );
//
//    if ($file = file_save_upload('my_file', $validators, 'public://')) {
//      $file = $form_state->getValue('my_file');
//    }
//    else {
//      \Drupal::messenger()->addMessage('my_file', 'Файл не был загружен');
//    }

    $name = $form_state->getValue('name');
    if(iconv_strlen($name) < 10 || iconv_strlen($name) > 100 ){
      $form_state -> setErrorByName('title',$this->t('Назма має містити від 10 до 100 символів'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $files = \Drupal::entityTypeManager()->getStorage('file')
      ->load($form_state->getValue('my_file')[0]);
    $filenames= $files->get('filename')->value;
    \Drupal::messenger()->addMessage('Картинка загружена');

    $query =\Drupal::database()->insert('a_news');
    $query -> fields([
      'name' => "{$form_state->getValue('name')}",
      'description_small' => "{$form_state -> getValue('description_small')['value']}",
      'description' => "{$form_state -> getValue('description')['value']}",
      'img' => $filenames,
    ]);

    $query->execute();
    \Drupal::messenger()->addMessage('All information saved');








  }



}
