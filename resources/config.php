<?php

// あらゆるリソースにアクセスを認めます。
header('Access-Control-Allow-Origin: *');

// DBライブラリ Idiorm の設定を行います。
ORM::configure('mysql:host=localhost;dbname=yarnet;charset=utf8mb4');
ORM::configure('username', 'dummy');
ORM::configure('password', 'dummy');

ORM::configure('id_column_overrides', [
  'favorite_spots' => ['your_id', 'place_id'],
]);
