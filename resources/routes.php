<?php

Router::post('/users', function() {
  $name = $_POST['name'] ?? NULL;
  $mail = $_POST['mail'] ?? NULL;
  $pass = $_POST['pass'] ?? NULL;

  $errors['name'] = validate_name($name);
  $errors['mail'] = validate_mail($mail);
  $errors['pass'] = validate_pass($pass);

  if ($errors = array_filter($errors, function($e) { return $e; })) {
    die(json_encode(['error' => $errors]));
  }

  $users = ORM::forTable('users')->create();
  $users->name = $name;
  $users->mail = $mail;
  $users->pass = password_hash($pass, PASSWORD_DEFAULT);
  $users->save();

  echo json_encode(['id' => $users->id]);
});

header('HTTP/1.0 404 Not Found');
die('{"error":"404 Not Found"}');
