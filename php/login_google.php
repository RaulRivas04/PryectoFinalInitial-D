<?php
require_once '../vendor/autoload.php';
session_start();

$client = new Google_Client();
$client->setClientId("11683329459-bbqhtq7ih0m6is2ifdnojn7t2lsatfd1.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("https://initial-d.lovestoblog.com/php/login_google_callback.php");
$client->addScope("email");
$client->addScope("profile");

header("Location: " . $client->createAuthUrl());
exit();