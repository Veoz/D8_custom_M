<?php


namespace Drupal\lendos\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class Edit  extends FormBase {

  public function getFormId() {
    return 'edit_lendos';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = \Drupal::database()->select('a_lendos', 'n');
    $query->fields('n', ['name', 'mail', 'tell', 'text', 'img','avatar','id']);
    $result = $query->execute()->fetchAll();

    foreach ($result as $value) {
      $edit = [
        'name' => $value->name,
        'tell'   => $value->tell,
        'text'   => $value->text,
        'mail'   => $value->mail,
        'img'    => $value->img,
        'avatar' => $value->avatar,
        'id' => $value->id,
      ];
    }
    $form_state->set('Comment_id', $edit['id']);
    $form_state->set('Comment_avatar', $edit['avatar']);
    $form_state->set('Comment_img', $edit['img']);
    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => 'Вкажіть ваше нове ім\'я',
      '#required'      => TRUE,
      '#default_value' => $edit['name'],
    ];
    $form['tell']   = [
      '#type'          => 'textfield',
      '#title'         => 'Залиште вашу нову електронну адресу тут, і ми з вами зв\'яжемось',
      '#required'      => TRUE,
      '#default_value' => $edit['tell'],

    ];
    $form['mail']   = [
      '#type'          => 'textfield',
      '#title'         => 'Залиште ваш новий номер телефону тут, і ми з вами зв\'яжемось',
      '#required'      => TRUE,
      '#default_value' => $edit['mail'],

    ];
    $form['text']              = [
      '#type'          => 'text_format',
      '#format'        => 'full_html',
      '#title'         => 'Залиште ваш новий відгук тут, будь ласка.',
      '#default_value' => $edit['text'],

    ];
    $form['my_file']           = [
      '#type'            => 'managed_file',
      '#name'            => 'my_file',
      '#title'           => t('Змініть зоображення за необхідності'),
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
      '#title'           => t('Змініть ваш Аватар за необхідності'),
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
    $name = $form_state->getValue('name');
    if (iconv_strlen($name) < 5 || iconv_strlen($name) > 100) {
      $form_state->setErrorByName('title', $this->t('Назва має містити від 5 до 100 символів'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {


    if (empty($form['my_file']['#value']['fids']) == TRUE) {
      $filenames = $form_state->get('Comment_img');
    }
    else {
      $files     = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('my_file')[0]);
      $filenames = $files->get('filename')->value;
    }

    if (empty($form['avatar']['#value']['fids']) == TRUE) {
      $avatar = $form_state->get('Comment_avatar');
    }
    else {
      $files     = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('avatar')[0]);
      $avatar = $files->get('filename')->value;
    }


    $id = $form_state->get('Comment_id');
    $query = \Drupal::database()->update('a_lendos');
    $query->condition('id', $id , '=');
    $query->fields([
      'name' => "{$form_state->getValue('name')}",
      'tell'   => "{$form_state -> getValue('tell')}",
      'mail'   => "{$form_state -> getValue('mail')}",
      'text'   => "{$form_state -> getValue('text')['value']}",
      'img'    => $filenames,
      'avatar' => $avatar,
    ]);

    $query->execute();
    $form_state->setRedirect('lendos.first_page');


  }

}
