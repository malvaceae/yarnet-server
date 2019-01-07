<?php

/******************************\
 *                            *
 *   プリフライトリクエスト   *
 *                            *
\******************************/

Router::options('.*', function() {
  header('Access-Control-Allow-Methods: GET,HEAD,POST,PUT,DELETE,CONNECT,OPTIONS,TRACE,PATCH');
  header('Access-Control-Allow-Headers: ' . getallheaders()['Access-Control-Request-Headers']);
});


/******************************\
 *                            *
 *          ユーザー          *
 *                            *
\******************************/

Router::post('/signup', function() {
  $name                  = $_POST['name']                  ?? NULL;
  $email                 = $_POST['email']                 ?? NULL;
  $password              = $_POST['password']              ?? NULL;
  $password_confirmation = $_POST['password_confirmation'] ?? NULL;

  $errors['name'] = validate_name($name, 'INSERT');
  $errors['email'] = validate_mail($email, 'INSERT');
  $errors['password'] = validate_pass($password, 'INSERT');
  $errors['password_confirmation'] = ($password !== $password_confirmation ? 'パスワードが一致しません。' : NULL);

  if ($errors = array_filter($errors, function($e) { return $e; })) {
    header('HTTP/1.0 400 Bad Request');
    die(json_encode(['error' => $errors]));
  }

  $users = ORM::forTable('users')->create();
  $users->name = $name;
  $users->mail = $email;
  $users->pass = password_hash($password, PASSWORD_DEFAULT);
  $users->save();

  echo json_encode(['id' => $users->id]);
});

Router::post('/signin', function() {
  $email    = $_POST['email']    ?? NULL;
  $password = $_POST['password'] ?? NULL;

  $errors['email']    = validate_mail($email, 'INSERT');
  $errors['password'] = validate_pass($password, 'INSERT');

  if ($errors = array_filter($errors, function($e) { return $e; })) {
    header('HTTP/1.0 400 Bad Request');
    die(json_encode(['error' => $errors]));
  }

  $users = ORM::forTable('users')
      ->selectMany('id', 'pass')
      ->where('mail', $email)
      ->findOne();

  if (!$users || !password_verify($password, $users->pass)) {
    header('HTTP/1.0 401 Unauthorized');
    die('{"error":"401 Unauthorized"}');
  }

  echo json_encode(['id' => $users->id]);
});


/******************************\
 *                            *
 *           観光地           *
 *                            *
\******************************/

Router::get('/tweets', function() {
  $q = $_GET['q'];
  if (!$q) die('[]');

  $array = ORM::forTable('twitter_tweets')
    ->select('twitter_tweets.id')
    ->select('twitter_tweets.date')
    ->select('twitter_tweets.body')
    ->select('twitter_users.name', 'user_name')
    ->select('twitter_users.screen_name', 'user_screen_name')
    ->select('twitter_users.profile_img', 'user_profile_img')
    ->select('twitter_photos.body', 'photo_body')
    ->join('twitter_users', 'user_id = twitter_users.id')
    ->join('twitter_photos', 'twitter_tweets.id = tweet_id')
    ->whereNotLike('body', 'RT%')
    ->whereLike('body', "%{$q}%")
    ->orderByDesc('date')
    ->limit(100)
    ->findArray();

  $tweets = [];
  foreach ($array as $tweet) {
    if (isset($tweets[$tweet['id']])) {
      $tweets[$tweet['id']]['photos'][] = $tweet['photo_body'];
    } else {
      $tweets[$tweet['id']] = [
        'id' => $tweet['id'],
        'date' => $tweet['date'],
        'body' => $tweet['body'],
        'user_name' => $tweet['user_name'],
        'user_screen_name' => $tweet['user_screen_name'],
        'user_profile_img' => $tweet['user_profile_img'],
        'photos' => [$tweet['photo_body']],
      ];
    }
  }
  $tweets = array_values($tweets);

  echo json_encode($tweets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});


Router::get('/messages', function() {
  $messages = ORM::forTable('messages')->findArray();

  echo json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::post('/messages', function() {
  $date = $_POST['date'] ?? NULL;
  $name = $_POST['name'] ?? NULL;
  $body = $_POST['body'] ?? NULL;

  $message = ORM::forTable('messages')->create();
  $message->date = $date;
  $message->name = $name;
  $message->body = $body;
  $message->save();

  echo json_encode(['id' => $message->id]);
});


Router::get('/users/(\\d+)/favorite_spots', function($id) {
  $favorite_spots = ORM::forTable('favorite_spots')
    ->where('your_id', $id)
    ->findArray();

  echo json_encode($favorite_spots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::post('/users/(\\d+)/favorite_spots', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('["B": "B"]');

  $spot = ORM::forTable('spots')->findOne($_POST['spot_id'] ?? -1);
  if (!$spot) die('["A": ' . $_POST['spot_id'] . ']');

  $favorite_spot = ORM::forTable('favorite_spots')->create();
  $favorite_spot->user_id = $user->id;
  $favorite_spot->spot_id = $spot->id;
  $favorite_spot->save();

  echo json_encode(compact('id'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

/******************************\
 *                            *
 *         404 エラー         *
 *                            *
\******************************/

header('HTTP/1.0 404 Not Found');
die('{"error":"404 Not Found"}');
