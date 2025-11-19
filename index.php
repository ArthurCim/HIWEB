<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  include __DIR__ . '/landing.php';
  exit();
}

include __DIR__ . '/dashboard.php';
