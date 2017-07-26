<?php

/*
 * обработчик всех AJAX-запросов на сервер.
 * typeQuery - тип запроса SQL
 * id - поле идентификации записи
 */

require_once __DIR__.DIRECTORY_SEPARATOR.'lib.php';

// проверка наличия обязательных входящих параметров:
if (! isset($_POST['typeQuery'])) {
    exit;
}

// с каким запросом/таблицей работаем?!
if (! isset($_POST['numQuery'])) {
    exit;
}

$typeQuery = $_POST['typeQuery'];
if ($typeQuery == 'delete' || $typeQuery == 'update' )  {
    if (! isset($_POST['id']) || empty($_POST['id'])) {
        exit;
    }
    $id = (int)($_POST['id'] ? : 0);
}

$param=[]; // параметры для запроса, массив
$query=''; // текст промежуточного запроса
$validSortOptions=['is_done', 'description', 'add_date'];
// формируем промежуточный запрос:
switch ($typeQuery) {
    case "delete":
        $param = [ "id" => $id ];
        $query="delete from task where task.id = :id ";
        break;
    case "update":
        if (isset($_POST['done'])) {
            $param = [ "done" => $_POST['done'], "id" => $id ];
            $query = "update task set is_done = :done  where task.id = :id";
        }
        else if (isset($_POST['description'])) {
                $param = ["desc" => $_POST['description'], "id" => $id ];
                $query = "update task set description = :desc where task.id = :id";
            }
        else if (isset($_POST['assigned'])) {
                $param = ["user" => $_POST['assigned'], "id" => $id ];
                $query = "update task set assigned_user_id = :user where task.id = :id ";
            }
        break;
    case "create":
        $date = date("Y-m-d H:i:s");
        $query = "insert into task (description, is_done, date_added, user_id, assigned_user_id) values (?, ?, ?, ?, ?) ";
        $param = [$_POST['description'], 0, $date, $_SESSION['user_id'], $_SESSION['user_id']];
        break;
}

// если есть промежуточный запрос - выполняем его:
if (! empty($query)) {
    try {
        $statement = $pdo->prepare($query);
        $statement->execute($param);
    } catch (PDOException $e) {
        echo "Ошибка обновления записи ($query) в БД: " . $e->getMessage() . '<br/>';
        exit;
    }
}

// нужна ли функциональная кнопка "делегировать"?
$delegate = false;
if ($_POST['numQuery'] == 'tab1') {
    $delegate = true;
}

// утанавливаем сортировку, если условия сортировки заданы:
$query = ${"mainQuery_".$_POST['numQuery']};

if (isset($_POST['sort']) && isset($_POST['column'])) {
    $query .= ' ORDER BY ';
    $asc = explode(',', $_POST['sort']);
    $col = explode(',', $_POST['column']);
    foreach ($asc as $key => $item) {
        if (! in_array($col[$key], $validSortOptions)) { continue; }
        $query .= $col[$key] . ' ' . $item;
        if ($key + 1 != count($asc)) {$query .= ','; }
    }
}

// обновляем текущий запрос в таблице:
echo prepareTable($query, $delegate);

