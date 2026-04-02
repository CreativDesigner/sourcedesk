<?php
session_start();

if (empty($_SESSION['ovh_secret']) || $_SESSION['ovh_secret'] != $_GET['os']) {
    die("Something went wrong, please try again. Do not reload product config page!");
}

if (empty($_SESSION['ovh_key'])) {
    die("Something went wrong, please try again. Please make sure cookies are enabled.");
}

echo "Your key is: " . $_SESSION['ovh_key'];
unset($_SESSION['ovh_key'], $_SESSION['ovh_secret']);
