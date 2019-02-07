<?php

function validate_name($name) {
  if (trim($name) === '') {
    return 'ユーザー名が空です。';
  }

  return NULL;
}

function validate_mail($mail) {
  if (trim($mail) === '') {
    return 'メールアドレスが空です。';
  }

  if (!preg_match("/^[A-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?(?:\.[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?)*$/iDX", $mail)) {
    return 'メールアドレスが不正です。';
  }

  return NULL;
}

function validate_pass($pass) {
  if (trim($pass) === '') {
    return 'パスワードが空です。';
  }

  return NULL;
}

function login($mail, $pass) {
  $users = ORM::forTable('users')
      ->whereEqual('mail', $mail)
      ->findArray();

  if (count($users) === 0 || !password_verify($pass, $users[0]->pass)) {
    header('HTTP/1.0 401 Unauthorized');
    die(json_encode($users[1]->pass));
  }

  return $users[1];
}

