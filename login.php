<?php
require_once __DIR__.DIRECTORY_SEPARATOR.'lib.php';

if (isset($_POST['enter']) ||(isset($_POST['reg']))) {
    unset($errors);

    if (!isset($_POST['password']) || (!isset($_POST['login']))) {
        $errors[] = 'Заполните все поля формы регистрации';
        exit;
    }
    $login = $_POST['login'];
    $passwd = md5($_POST['password']);

// нажали кнопку "вход":
    if (isset($_POST['enter'])) {
        $res = login($login, $passwd, 2);
        if ((int)$res === 0) {
            $errors[] = "Ошибка авторизации. Неверный пароль или логин ($res)";
        }
    } // нажали кнопку "регистрация":
    elseif (isset($_POST['reg'])) {
        $res = login($login, $passwd, 1);
        if ((int)$res != 0) {
            $errors[] = "Ошибка регистрации. Логин '$login' уже встречается в базе ($res)";
        }
        else {
            $res = login($login, $passwd, 3);
        }
    }

    if (count($errors) == 0) {
        $_SESSION['login'] = $login;
        $_SESSION['user_id'] = $res;
        header('location: index.php');
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Список дел TODO</title>
    <link rel="stylesheet" href="login.css">
    <meta charset="utf-8">
</head>
<body>
<div id="container">
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="name">Логин:</label>
        <input type="name" name="login" required>
        <label for="username">Пароль:</label>
        <input type="password" name="password" required>
        <div id="lower">
            <input type="submit" name="reg" value="Регистрация">
            <input type="submit" name="enter" value="Войти">
        </div>
    </form>
</div>
<p class = "errors">Ошибки формы: <?php var_dump($errors);?></p>
</html>
