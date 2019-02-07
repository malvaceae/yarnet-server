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

  echo json_encode(['id' => $users->id, 'name' => $users->name]);
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
      ->selectMany('id', 'name', 'pass')
      ->where('mail', $email)
      ->findOne();

  if (!$users || !password_verify($password, $users->pass)) {
    header('HTTP/1.0 401 Unauthorized');
    die('{"error":"401 Unauthorized"}');
  }

  echo json_encode(['id' => $users->id, 'name' => $users->name]);
});

Router::get('/users/(\\d+)', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  echo json_encode(['name' => $user->name, 'mail' => $user->mail]);
});

Router::post('/users/(\\d+)', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  $name                  = $_POST['name']                  ?? NULL;
  $email                 = $_POST['email']                 ?? NULL;
  $password              = $_POST['password']              ?? NULL;
  $password_confirmation = $_POST['password_confirmation'] ?? NULL;

  if (!is_null($name)) {
    $errors['name'] = validate_name($name, 'INSERT');
    $user->name = $name;
  }

  if (!is_null($email)) {
    $errors['email'] = validate_mail($email, 'INSERT');
    $user->mail = $email;
  }

  if (!is_null($password) && $password !== '') {
    $errors['password'] = validate_pass($password, 'INSERT');
    $errors['password_confirmation'] = ($password !== $password_confirmation ? 'パスワードが一致しません。' : NULL);
    $user->pass = password_hash($password, PASSWORD_DEFAULT);
  }

  if ($errors = array_filter($errors, function($e) { return $e; })) {
    header('HTTP/1.0 400 Bad Request');
    die(json_encode(['error' => $errors]));
  }

  $user->save();

  echo json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
  $place_id = $_GET['address'] ?? NULL;
  if (!$place_id) die('[]');

  $messages = ORM::forTable('messages')
    ->where('place_id', $place_id)
    ->findArray();

  echo json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::post('/messages', function() {
  $name = $_POST['name'] ?? '';
  $body = $_POST['body'] ?? '';

  $user_id = $_POST['user_id'] ?? NULL;
  $place_id = $_POST['address'] ?? NULL;
  if (!$place_id) die('[]');

  $message = ORM::forTable('messages')->create();
  $message->user_id = $user_id;
  $message->place_id = $place_id;
  $message->date = date('Y-m-d H:i:s');
  $message->name = $name ?: NULL;
  $message->body = $body;
  $message->save();

  echo json_encode(['date' => $message->date, 'body' => $message->body]);
});


Router::get('/users', function() {
  $q = $_GET['q'];
  if (!$q) die('[]');

  $users = ORM::forTable('users')
    ->select('id')
    ->select('name')
    ->whereLike('name', '%' . $q . '%')
    ->findArray();

  echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::get('/users/(\\d+)/favorite_users', function($id) {
  $favorite_spots = ORM::forTable('favorite_users')
    ->select('id')
    ->select('name')
    ->join('users', 'favorite_users.user_id = users.id')
    ->where('your_id', $id)
    ->findArray();

  echo json_encode($favorite_spots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::post('/users/(\\d+)/favorite_users', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  $user_id = $_POST['user_id'] ?? NULL;
  if (!$user_id) die('[]');

  $favorite_user = ORM::forTable('favorite_users')->create();
  $favorite_user->your_id = $user->id;
  $favorite_user->user_id = $user_id;
  $favorite_user->save();

  echo json_encode(compact('id'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::delete('/users/(\\d+)/favorite_users', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  parse_str(file_get_contents('php://input'), $params);
  $user_id = $params['user_id'] ?? NULL;
  if (!$user_id) die('[]');

  $favorite_spot = ORM::forTable('favorite_users')
    ->where('your_id', $id)
    ->where('user_id', $user_id)
    ->deleteMany();

  echo json_encode(compact('id'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});


Router::get('/users/(\\d+)/favorite_spots', function($id) {
  $favorite_spots = ORM::forTable('favorite_spots')
    ->where('your_id', $id)
    ->orderByAsc('order')
    ->findArray();

  echo json_encode($favorite_spots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::post('/users/(\\d+)/favorite_spots', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  $place_id = $_POST['place_id'] ?? NULL;
  if (!$place_id) die('[]');

  $favorite_spot = ORM::forTable('favorite_spots')->create();
  $favorite_spot->your_id    = $user->id;
  $favorite_spot->place_id   = $place_id;
  $favorite_spot->place_icon = $_POST['place_icon'];
  $favorite_spot->place_name = $_POST['place_name'];
  $favorite_spot->place_lat  = $_POST['place_lat'];
  $favorite_spot->place_lng  = $_POST['place_lng'];
  $favorite_spot->order      = 2147483647;
  $favorite_spot->save();

  echo json_encode(compact('id'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::put('/users/(\\d+)/favorite_spots/reorder', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  parse_str(file_get_contents('php://input'), $params);
  $place_ids = $params['place_id'] ?? [];
  if (!$place_ids) die('[]');

  foreach ($place_ids as $i => $place_id) {
    $favorite_spots = ORM::forTable('favorite_spots')
      ->where('your_id', $id)
      ->where('place_id', $place_id)
      ->findMany();
    foreach ($favorite_spots as $favorite_spot){
      $favorite_spot->order = $i;
      $favorite_spot->save();
    }
  }

  echo json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Router::delete('/users/(\\d+)/favorite_spots', function($id) {
  $user = ORM::forTable('users')->findOne($id);
  if (!$user) die('[]');

  parse_str(file_get_contents('php://input'), $params);
  $place_id = $params['place_id'] ?? NULL;
  if (!$place_id) die('[]');

  $favorite_spot = ORM::forTable('favorite_spots')
    ->where('your_id', $id)
    ->where('place_id', $place_id)
    ->deleteMany();

  echo json_encode(compact('id'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});


Router::get('/wikipedia', function() {
  $keyword  = $_GET['keyword']  ?? NULL;
  $callback = $_GET['callback'] ?? NULL;
  if (!$keyword || !$callback) die('[]');

  echo file_get_contents('http://wikipedia.simpleapi.net/api?output=json&callback=' . $callback . '&keyword=' . $keyword);
});

/******************************\
 *                            *
 *         404 エラー         *
 *                            *
\******************************/

header('HTTP/1.0 404 Not Found');
die('{"error":"404 Not Found"}');
