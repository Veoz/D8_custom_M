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
   $form['actions']['submit'] = [
     '#type' => 'submit',
     '#name' => 'submit',
     '#value' => 'Зберегти',
   ];

   return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $name = $form_state->getValue('name');
    if(iconv_strlen($name) < 10 || iconv_strlen($name) > 100 ){
      $form_state -> setErrorByName('title',$this->t('Назма має містити від 10 до 100 символів'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query =\Drupal::database()->insert('a_news');
    $query -> fields([
      'name' => "{$form_state->getValue('name')}",
      'description_small' => "{$form_state -> getValue('description_small')['value']}",
      'description' => "{$form_state -> getValue('description')['value']}"
    ]);
    $query->execute();
    drupal_set_message('All information saved ');
  }

}
