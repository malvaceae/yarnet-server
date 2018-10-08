<?php

Router::post('/users', function() {
  $name = $_POST['name'] ?? NULL;
  $mail = $_POST['mail'] ?? NULL;
  $pass = $_POST['pass'] ?? NULL;

  if (empty($name)) die('{"error":"Name Empty"}');
  if (empty($mail)) die('{"error":"Mail Empty"}');
  if (empty($pass)) die('{"error":"Pass Empty"}');

  $users = ORM::forTable('users')->create();
  $users->name = $name;
  $users->mail = $mail;
  $users->pass = password_hash($pass, PASSWORD_DEFAULT);
  $users->save();

  echo json_encode(['id' => $users->id]);
});

header('HTTP/1.0 404 Not Found');
die('{"error":"404 Not Found"}');
