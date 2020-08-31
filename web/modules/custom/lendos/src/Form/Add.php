<?php

namespace Drupal\lendos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class Add extends FormBase {

  public function getFormId() {
    return 'add_lendos';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => 'Вкажіть ваше ім\'я',
      '#required'      => TRUE,
    ];
    $form['tell']   = [
      '#type'          => 'textfield',
      '#title'         => 'Залиште вашу електронну адресу тут, і ми з вами зв\'яжемось',
      '#required'      => TRUE,
    ];
    $form['mail']   = [
      '#type'          => 'textfield',
      '#title'         => 'Залиште ваш номер телефону тут, і ми з вами зв\'яжемось',
      '#required'      => TRUE,
    ];

    $form['text']              = [
      '#type'          => 'text_format',
      '#format'        => 'full_html',
      '#title'         => 'Залиште ваш відгук тут, будь ласка.',
//      '#attributes'    => [
//      ],
//      '#required'      => TRUE,
//      '#cols'          => 60,
//      '#resizable'     => TRUE,
//      '#rows'          => 13,
    ];
    $form['my_file']           = [
      '#type'            => 'managed_file',
      '#name'            => 'my_file',
      '#title'           => t('Додати зоображення'),
      '#description'     => t('Виберіть картинку для коментаря.(до 5 мб)' .
        '.'),
      '#upload_location' => 'public://lendos_file/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [5000000],
        'file_validate_is_image'   => [],
      ],
    ];
    $form['avatar']           = [
      '#type'            => 'managed_file',
      '#name'            => 'avatar',
      '#title'           => t('Avatar'),
      '#description'     => t('Виберіть Ваш аватар. (до 2 мб)'),
      '#upload_location' => 'public://lendos_avatar/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [2000000],
        'file_validate_is_image'   => [],
      ],
    ];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#name'  => 'submit',
      '#value' => 'Зберегти',
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
//    $validators = [
//      'file_validate_is_image'   => [],
//      // Проверка, действительно ли файл является изображением
//      'file_validate_extensions' => ['png gif jpg jpeg'],
//      // Проверка на расширения
//    ];

    $name = $form_state->getValue('name');
    if (iconv_strlen($name) < 5 || iconv_strlen($name) > 100) {
      $form_state->setErrorByName('title', $this->t('Назва має містити від 5 до 100 символів'));
    }
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (empty($form['my_file']['#value']['fids']) == TRUE) {
      $filenames = 'none';
    }
    else {
      $files     = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('my_file')[0]);
      $filenames = $files->get('filename')->value;
    }

    if (empty($form['avatar']['#value']['fids']) == TRUE) {
      $avatar = 'default_avatar.png';
    }
    else {
      $files     = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('avatar')[0]);
      $avatar = $files->get('filename')->value;
    }



    $query = \Drupal::database()->insert('a_lendos');
    $query->fields([
      'name' => "{$form_state->getValue('name')}",
      'tell'   => "{$form_state -> getValue('tell')}",
      'mail'   => "{$form_state -> getValue('mail')}",
      'text'   => "{$form_state -> getValue('text')['value']}",
      'img'    => $filenames,
      'avatar' => $avatar,
    ]);

    $query->execute();


  }


}