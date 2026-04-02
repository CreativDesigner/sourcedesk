<?php

if (!empty($_POST['age']) && is_numeric($_POST['age'])) {
    if (empty($_SESSION['minage']) || $_SESSION['minage'] < $_POST['age']) {
        $_SESSION['minage'] = $_POST['age'];
    }
}

exit;