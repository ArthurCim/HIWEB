<?php
// Entry point: show landing page to guests, dashboard to authenticated users
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  // Not logged in: show the landing page which contains the login CTA
  include __DIR__ . '/landing.php';
  exit();
}

// Logged in: show dashboard
include __DIR__ . '/dashboard.php';
