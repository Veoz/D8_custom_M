<?php

namespace Drupal\lendos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use PhpParser\Node\Stmt\Unset_;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

class Add extends FormBase {


  public function getFormId() {
    return 'add_lendos';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    if ($id != NULL) {
      $query = \Drupal::database()->select('a_lendos', 'n');
      $query->fields('n', [
        'name',
        'mail',
        'tell',
        'text',
        'img',
        'avatar',
        'id'
      ]);
      $query->condition('id', $id, '=');
      $result = $query->execute()->fetchAll();

      foreach ($result as $value) {
        $edit = [
          'name'   => $value->name,
          'tell'   => $value->tell,
          'text'   => $value->text,
          'mail'   => $value->mail,
          'img'    => $value->img,
          'avatar' => $value->avatar,
          'id'     => $value->id,
        ];
      }
      $form_state->set('Comment_id', $edit['id']);
      $form_state->set('Comment_avatar', $edit['avatar']);
      $form_state->set('Comment_img', $edit['img']);
      $form_state->set('is_idd', $id);
    }else{
       $form_state->set('is_idd', $id);
    }

    $form['name']              = [
      '#type'          => 'textfield',
      '#title'         => 'Вкажіть ваше ім\'я',
      '#required'      => TRUE,
      '#default_value' => $edit['name'] ?? '',
    ];
    $form['mail']              = [
      '#type'          => 'email',
      '#title'         => 'Залиште вашу електронну адресу тут, і ми з вами зв\'яжемось',
      '#required'      => TRUE,
      '#default_value' => $edit['mail'] ?? '',
    ];
    $form['tell']              = [
      '#type'          => 'textfield',
      '#title'         => 'Залиште ваш номер телефону тут, і ми з вами зв\'яжемось',
      '#required'      => TRUE,
      '#default_value' => $edit['tell'] ?? '',
    ];
    $form['text']              = [
      '#type'          => 'textarea',
      '#title'         => 'Залиште ваш відгук тут, будь ласка.',
      '#required'      => TRUE,
      '#cols'          => 60,
      '#rows'          => 13,
      '#default_value' => $edit['text'] ?? '',
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
    //Name validate by regular expression
    $name = $form_state->getValue('name');
    // $name_patern = preg_match("/^[a-zA-Z0-9А-Яа-я_ -'.@]+[a-zA-Z0-9А-Яа-я_ -'.@]$/u",$name);
    $name_patern = preg_match("/^[a-zA-Z0-9А-Яа-яїЇіІ_ -'.]+[a-zA-Z0-9А-Яа-яїЇіІ_ -'.]$/u", $name);

    if (iconv_strlen($name) < 2 || iconv_strlen($name) > 100 || !$name_patern) {
      $form_state->setErrorByName('name', $this->t('ім\'я має містити лише букви та цифри від 2 до 100 символів'));
    }
    //Text validate by regular expression
    $text        = $form_state->getValue('text');
    $reg         = "/^[a-zA-Z0-9А-Яа-яїЇіІ_ -'.,@?!–();\n\r\t]+[a-zA-Z0-9А-Яа-яїЇіІ_ -'.,@?!–();\n\r\t]$/u";
    $text_patern = preg_match($reg, $text);

    if (!$text_patern) {
      $form_state->setErrorByName('text', $this->t('Текст має містити лише букви та цифри'));
    }
    //Telephone validate by regular expression
    $tell    = $form_state->getValue('tell');
    $pattern = preg_match("/^[0-9]{10}$/", $tell);

    if (!$pattern) {
      $form_state->setErrorByName('tell', $this->t('телефон має містити тільки цифри, довжиною 10 символів'));
    }
    //Mail validate by regular expression
    $mail         = $form_state->getValue('mail');
    $pattern_mail = preg_match("/[a-zA-Z0-9]+@[a-zA-Z0-9]+\.[a-zA-Z0-9]+/", $mail);

    if (!$pattern_mail) {
      $form_state->setErrorByName('mail', $this->t("Вказана адреса е-пошти не є коректною."));
    }
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id =$form_state->getValue('is_idd');
    if ( $id == NULL) {
      $this->insertDB($form, $form_state);
    }else{
      $this->updateDB($form, $form_state);
    }
  }


  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {


    $ajax_response = new AjaxResponse();
    $message       = [
      '#theme'           => 'status_messages',
      '#message_list'    => drupal_get_messages(),
      '#status_headings' => [
        'status'  => t('Status message'),
        'error'   => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $messages      = \Drupal::service('renderer')->render($message);


    if ($form_state->hasAnyErrors()) {
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    }
    else {
      $this->setPhotoPermanent('my_file', $form_state);
      $this->setPhotoPermanent('avatar', $form_state);
      $url     = Url::fromRoute('lendos.first_page');
      $command = new RedirectCommand($url->toString());
      $ajax_response->addCommand($command);
      $message = \Drupal::messenger()->addMessage(
        "Comment  by '{$form_state->getValue('name')}' has been created!",
        'status'
      );
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
      // $ajax_response->addCommand(new RedirectCommand($_SERVER['PHP_SELF']));
    }
    return $ajax_response;
  }

  public function setPhotoPermanent($photoName, $form_state) {
    $photoFid = $form_state->getValue($photoName);
    if (!empty($photoFid[0])) {
      $photoFid = $photoFid[0];
      $photo    = \Drupal\file\Entity\File::load($photoFid);
      $photo->setPermanent();
      $photo->save();
    }
  }


  public function insertDB($form, $form_state) {
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
    $name = htmlspecialchars($form_state->getValue('name'));
    $tell = htmlspecialchars($form_state->getValue('tell'));
    $mail = htmlspecialchars($form_state->getValue('mail'));
    $text = htmlspecialchars($form_state->getValue('text'));

    $query = \Drupal::database()->insert('a_lendos');
    $query->fields([

      'name'   => "{$name}",
      'tell'   => "{$tell}",
      'mail'   => "{$mail}",
      'text'   => "{$text}",
      'img'    => $filenames,
      'avatar' => $avatar,

    ]);


    $query->execute();
  }

  public function updateDB($form, $form_state) {

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
      $files  = \Drupal::entityTypeManager()->getStorage('file')
        ->load($form_state->getValue('avatar')[0]);
      $avatar = $files->get('filename')->value;
    }

    $name = htmlspecialchars($form_state->getValue('name'));
    $tell = htmlspecialchars($form_state->getValue('tell'));
    $mail = htmlspecialchars($form_state->getValue('mail'));
    $text = htmlspecialchars($form_state->getValue('text'));

    $id    = $form_state->get('Comment_id');
    $query = \Drupal::database()->update('a_lendos');
    $query->condition('id', $id, '=');
    $query->fields([

      'name'   => "{$name}",
      'tell'   => "{$tell}",
      'mail'   => "{$mail}",
      'text'   => "{$text}",
      'img'    => $filenames,
      'avatar' => $avatar,

    ]);

    $query->execute();

  }

}
