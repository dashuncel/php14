<?php

require_once 'mydata.php';
session_start();

$host='localhost';
$dbport=3306;

$user=LOGIN;
$password=PASSWD;
$database=LOGIN;
/*
$user='root';
$password='';
$database='global';
*/
$errors=[];

/* данные запросы не пересекаются благодаря условию, накладываемому на поле task.user_id
* потому id смело пишем в качестве tr#id
*/
// мои задачи:
$mainQuery_tab1="SELECT task.id as 'id',
                     task.description as 'taskname', 
                     task.date_added as 'data', 
                     task.is_done as 'done',
                     user.login as 'resp' 
              FROM task JOIN user ON task.assigned_user_id = user.id WHERE task.user_id = ? ";

// делегированные мне задачи:
$mainQuery_tab2="SELECT task.id as 'id',
                     task.description as 'taskname', 
                     task.date_added as 'data', 
                     task.is_done as 'done',
                     user.login as 'resp' 
              FROM task JOIN user ON task.user_id = user.id WHERE task.assigned_user_id = ? and task.user_id <> ? ";

$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

try
{
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, $opt);
}
catch (PDOException $e)
{
    $errors[] = 'Ошибка подключения к БД: '.$e->getMessage().'<br/>';
}

/*
*  Выполняет запрос $query
*  output - HTML таблица
*/
function prepareTable($query, $delegate) {
    global $pdo;

    if (! isAutorized()) {
        header("Location: login.php");
    }

    if (substr_count($query,'?') == 2) {
        $param = [$_SESSION['user_id'], $_SESSION['user_id']];
    } else {
        $param = [$_SESSION['user_id']];
    }

    try {
        $statement = $pdo->prepare($query);
        $statement->execute($param);
    }
    catch (PDOException $e) {
        $errors = "Ошибка отправки запроса '$query' к БД: ".$e->getMessage().'<br/>';
        return $errors;
    }

    $rows=$statement->fetchAll();

    if (empty($rows) || ! is_array($rows)) {
        return '';
    }

    $str='';
    foreach ($rows as $row) {
        if ($row['done'] == 0) {
            $done='undone';
            $title="не выполнено";
        }
        else {
            $done='done';
            $title='выполнено';
        }

        $a = "<a title='редактировать' href='#' class='edit'><img src='.\img\\ed.png'></a>";
        $a .= "  <a title='удалить' href='#' class='del'><img src='.\img\drop.png'></a>";

        // нужна кнопка с делегированием:
        if ($delegate) {
            $a .= "  <a title='делегировать' href='#' class='delegate'><img src='.\img\delegate.png'></a>   ";
            $a .= getUsers();
        }

        $str.="<tr id={$row['id']}>";
        $str.="<td>{$row['data']}</td><td >{$row['taskname']}</td><td class=$done title=$title></td><td>{$row['resp']}</td><td>$a</td>";
        $str.="</tr>";
    }
    return $str;
}

/* проверка существования логина:
 * $login - логин
 * $passwd - шифрованный пароль
 * $mode - режим работы процедуры:
 *      1 - проверка существования логина перед регистрацией
 *      2 - аутентификация
 *      3 - регистрация
 * return $id - id пользователя
*/
function login($login, $passwd, $mode) {
    global $pdo;
    global $errors;
    $id=0; // айди юзера

    switch ($mode) {
        case "1":
            $query = "SELECT id FROM user WHERE login= :login";
            $param = [ "login" => $login ];
            break;
        case "2":
            $query = "SELECT id FROM user WHERE login= :login and password = :passwd";
            $param = [ "login" => $login, "passwd" => $passwd ];
            break;
        case "3":
            $query = "insert into user (login, password) values (:login, :passwd)";
            $param = [ "login" => $login, "passwd" => $passwd ];
            break;
    }

    try {
        $statement = $pdo->prepare($query);
        $statement->execute($param);
        if ($mode != "3") {$rows = $statement->fetchAll();}
    }
    catch (PDOException $e) {
        $errors[] = "Ошибка отправки запроса '$query' к БД: ".$e->getMessage().'<br/>';
        return 0;
    }

    // что мы возвращаем в качестве айди пользователя:
    switch ($mode) {
        case "3": // айди созданной учетной записи:
            $id = $pdo->lastInsertId();
            break;
        case "2": // айди найденной учетной записи:
            if (count($rows) == 1) {
                $id = $rows[0]['id'];
            }
            else $id = 0;
            break;
        case "1": // количество найденных записей
            if (!isset($rows)) {
                $id = 0;
            } else {
                $id = count($rows);
            }
            break;
    }
    return($id);
}

// возвращает список пользователей для select'а
function getUsers() {
    global $pdo;
    $htmlStr = '';

    try {
        $statement = $pdo->prepare('select login, id from user where login <> ?');
        $statement->execute([$_SESSION['login']]);
    }
    catch (PDOException $e) {
        $result = "Ошибка отправки запроса '$query' к БД: ".$e->getMessage().'<br/>';
        return $result;
    }

    $rows=$statement->fetchAll();

    $htmlStr = '<select name="users">';
    foreach ($rows as $row) {
        $htmlStr.="<option value={$row['id']}>{$row['login']}</option>";
    }
    $htmlStr .= '</select>';
    return($htmlStr);
}

function isAutorized() {
    return !empty($_SESSION['user_id']);
}

function logout()
{
    return session_destroy();
}