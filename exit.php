<?php
require_once __DIR__.DIRECTORY_SEPARATOR.'lib.php';

if (isAutorized()) {
    logout();
}
header("Location: login.php");

