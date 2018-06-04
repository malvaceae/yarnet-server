<?php

/**
 * ルーティング機能を提供するユーティリティクラス。
 */
class Router {
  public static function redirect($uri) {
    header("Location: $uri", false, 303);
    exit("Redirect to $uri");
  }

  public static function __callStatic($name, $args) {
    self::run(strtoupper($name), $args[0], $args[1]);
  }

  private static function run($method, $uri, $action) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) return;

    $pattern = "#^$uri(?:\\?.*)?\$#DX";
    $subject = $_SERVER['REQUEST_URI'];
    preg_match($pattern, $subject, $matches);

    if (!$matches) return;
    array_shift($matches);
    call_user_func_array($action, $matches) !== false and exit;
  }
}
