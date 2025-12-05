<?php
// CLI runner to include endpoint
$_GET['id_answer'] = $argv[1] ?? '';
// emulate session
session_start();
$_SESSION['login'] = true;
include 'ajax/get_answer_detail.php';
