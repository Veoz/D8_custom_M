<?php

namespace Drupal\about\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class Add extends FormBase {

  public function getFormId() {
    return 'edit_about';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $query  = \Drupal::database()->query("SELECT * FROM `a_about`");
    $result = $query->fetchAll();

    foreach ($result as $row) {
      $edit = [
        'slogan' => $row->slogan,
        'tell'   => $row->tell,
        'text'   => $row->text,
        'mail'   => $row->mail,
        'img'    => $row->img,
      ];
    }
    $default_img = $edit['img'];


    $form['slogan'] = [
      '#type'          => 'textfield',
      '#title'         => 'Дивіз',
      '#required'      => TRUE,
      '#default_value' => $edit['slogan'],
    ];
    $form['tell']   = [
      '#type'          => 'textfield',
      '#title'         => 'Контактний номер телефону, для зв\'язку з користувачами',
      '#required'      => TRUE,
      '#default_value' => $edit['tell'],
    ];
    $form['mail']   = [
      '#type'          => 'textfield',
      '#title'         => 'Контактна адреса електронної пошти, для з\'вязку з користувачами',
      '#required'      => TRUE,
      '#default_value' => $edit['mail'],
    ];

    $form['text']              = [
      '#type'          => 'text_format',
      '#format'        => 'full_html',
      '#title'         => 'Про нас! (не в двох словах...)',
      '#attributes'    => [
      ],
      '#required'      => TRUE,
      '#cols'          => 60,
      '#resizable'     => TRUE,
      '#rows'          => 13,
      '#default_value' => $edit['text'],
    ];
    $form['my_file']           = [
      '#type'            => 'managed_file',
      '#name'            => 'my_file',
      '#title'           => t('Картинка'),
      '#size'            => 200,
      '#description'     => t('Виберіть картинку для заміни.'),
      '#upload_location' => 'public://about_file/',
    ];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#name'  => 'submit',
      '#value' => 'Зберегти',
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validators = [
      'file_validate_is_image'   => [],
      // Проверка, действительно ли файл является изображением
      'file_validate_extensions' => ['png gif jpg jpeg'],
      // Проверка на расширения
    ];

    $name = $form_state->getValue('slogan');
    if (iconv_strlen($name) < 5 || iconv_strlen($name) > 100) {
      $form_state->setErrorByName('title', $this->t('Назва має містити від 5 до 100 символів'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (empty($form['my_file']['#value']['fids']) == TRUE) {
      $filenames = 'about.jpg';
    }
    else {
      $files     = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('my_file')[0]);
      $filenames = $files->get('filename')->value;
    }

    $query = \Drupal::database()->update('a_about');
    $query->fields([
      'slogan' => "{$form_state->getValue('slogan')}",
      'tell'   => "{$form_state -> getValue('tell')}",
      'mail'   => "{$form_state -> getValue('mail')}",
      'text'   => "{$form_state -> getValue('text')['value']}",
      'img'    => $filenames,
    ]);

    $query->execute();
    //    \Drupal::messenger()->addMessage('All information saved');

  }


}
