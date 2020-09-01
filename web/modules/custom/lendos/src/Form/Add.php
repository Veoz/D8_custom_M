<?php

namespace Drupal\lendos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use PhpParser\Node\Stmt\Unset_;


class Add extends FormBase {


  public function getFormId() {
    return 'add_lendos';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['name']              = [
      '#type'     => 'textfield',
      '#title'    => 'Вкажіть ваше ім\'я',
      '#required' => TRUE,
    ];
    $form['mail']              = [
      '#type'     => 'email',
      '#title'    => 'Залиште вашу електронну адресу тут, і ми з вами зв\'яжемось',
      '#required' => TRUE,
    ];
    $form['tell']              = [
      '#type'     => 'textfield',
      '#title'    => 'Залиште ваш номер телефону тут, і ми з вами зв\'яжемось',
      '#required' => TRUE,
    ];
    $form['text']              = [
      '#type'     => 'textarea',
      '#title'    => 'Залиште ваш відгук тут, будь ласка.',
      '#required' => TRUE,
      '#cols'     => 60,
      '#rows'     => 13,

    ];
    $form['my_file']           = [
      '#type'              => 'managed_file',
      '#name'              => 'my_file',
      '#title'             => t('Додати зоображення'),
      '#description'       => t('Виберіть картинку для коментаря.(до 5 мб)' .
        '.'),
      '#upload_location'   => 'public://lendos_file/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size'       => [5000000],
        'file_validate_is_image'   => [],
      ],
    ];
    $form['avatar']            = [
      '#type'              => 'managed_file',
      '#name'              => 'avatar',
      '#title'             => t('Avatar'),
      '#description'       => t('Виберіть Ваш аватар. (до 2 мб)'),
      '#upload_location'   => 'public://lendos_avatar/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size'       => [2000000],
        'file_validate_is_image'   => [],
      ],
    ];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#name'  => 'submit',
      '#value' => 'Зберегти',
      '#ajax'  => [
        'callback' => '::ajaxSubmitCallback',
        'event'    => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    if (iconv_strlen($name) < 3 || iconv_strlen($name) > 100) {
      $form_state->setErrorByName('name', $this->t('ім\'я має містити від 3 до 100 символів'));
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
      $files  = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('avatar')[0]);
      $avatar = $files->get('filename')->value;
    }


    $query = \Drupal::database()->insert('a_lendos');
    $query->fields([
      'name'   => "{$form_state->getValue('name')}",
      'tell'   => "{$form_state -> getValue('tell')}",
      'mail'   => "{$form_state -> getValue('mail')}",
      'text'   => "{$form_state -> getValue('text')}",
      'img'    => $filenames,
      'avatar' => $avatar,

    ]);

    $query->execute();


  }


  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $comment = $this->getInfo();

    $ajax_response = new AjaxResponse();


    if ($form_state->hasAnyErrors()) {

//      $ajax_response->addCommand(new HtmlCommand('#add-lendos', $erno));
      $ajax_response->addCommand(new HtmlCommand('#comment', $comment));
    }
    else {
      $ok = $this->successForm();
      $ajax_response->addCommand(new HtmlCommand('#add-lendos', $ok));
      $ajax_response->addCommand(new HtmlCommand('#comment', $comment));

    }

    return $ajax_response;

  }

  public function getInfo() {
    global $base_url;

    $lendos = [];

    $query = \Drupal::database()->select('a_lendos', 'n');
    $query->fields('n', [
      'name',
      'mail',
      'tell',
      'text',
      'img',
      'avatar',
      'id',
      'date_create',
    ]);
    $query->orderBy('id', "DESC");
    $result = $query->execute()->fetchAll();

    foreach ($result as $row) {
      array_push($lendos, [
        'name'   => $row->name,
        'text'   => $row->text,
        'tell'   => $row->tell,
        'mail'   => $row->mail,
        'img'    => $row->img,
        'date'   => $row->date_create,
        'avatar' => $row->avatar,
      ]);
    }

    $data = [
      'lendos' => $lendos,

    ];


    return [
      '#theme'    => 'comments',
      '#data'     => $data,
      '#base_url' => $base_url,
    ];
  }

  public function successForm() {
    global $base_url;
    return [
      '#theme'    => 'success_add_ajax',
      '#base_url' => $base_url,
    ];
  }

}
