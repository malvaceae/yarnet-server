<?php

function validate_name($name) {
  if (trim($name) === '') {
    return '入力欄が空です。';
  }

  return NULL;
}

function validate_mail($mail) {
  if (trim($mail) === '') {
    return '入力欄が空です。';
  }

  if (!preg_match("/^[A-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?(?:\.[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?)*$/iDX", $mail)) {
    return '不正な形式です。';
  }

  return NULL;
}

function validate_pass($pass) {
  if (trim($pass) === '') {
    return '入力欄が空です。';
  }

  return NULL;
}
