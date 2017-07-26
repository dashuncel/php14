<?php
require_once __DIR__.DIRECTORY_SEPARATOR.'lib.php';
if (! isAutorized()) {
    header("Location: login.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Список дел TODO</title>
    <link rel="stylesheet" href="index.css">
    <meta charset="utf-8">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
</head>
<body>
<div class="modal-wrapper">
    <div class="modal">
        <div class="head">
            <a class="btn-close trigger" href='#'></a>
            <div class="title"></div>
        </div>
        <form>
            <textarea name="desc" rows="4" cols="65" placeholder="Описание дела" required></textarea>
            <input type="button" value="Сохранить" class="trigger creater">
        </form>
    </div>
</div>
<div class="page-wrapper">
    <input type="button" class="trigger adder" value="Добавить новое дело" name="add">
    <div class="exit">
        <p>Здравствуй, <?= $_SESSION['login'] ?>
            <a href="exit.php">Выход</a>
        </p>
    </div>
    <table>
        <caption>Список дел пользователя <?php echo $_SESSION['login'] ?></caption>
        <thead class="tab1">
        <tr>
           <th data-sort="asc" data-col="date_added">Дата</th>
           <th data-sort="asc" data-col="description">Дело</th>
           <th data-sort="asc" data-col="is_done">Статус</th>
            <th data-col="user">Исполнитель</th>
           <th>Действия</th>
        </tr>
        </thead>
        <tbody class="tab1">
        <?php echo prepareTable($mainQuery_tab1, true) ?>
        </tbody>
    </table>

    <table>
        <caption>Список дел, делигированных пользователю <?php echo $_SESSION['login'] ?> </caption>
        <thead class="tab2">
        <tr>
            <th data-sort="asc" data-col="date_added">Дата</th>
            <th data-sort="asc" data-col="description">Дело</th>
            <th data-sort="asc" data-col="is_done">Статус</th>
            <th data-col="user">Делегировано кем</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody class="tab2">
        <?php echo prepareTable($mainQuery_tab2, false) ?>
        </tbody>
    </table>

    <div>
    <dl>
        <dt>Сортировка - </dt>
        <dd>клик по загловку таблицы, имеющим значок сортировки</dd>
        <dt>Изменение статуса - </dt>
        <dd>клик по ячейке в колонке "Статус"</dd>
        <dt>Делегирование - </dt>
        <dd>клик по кнопке <img src="./img/delegate.png"> в колонке "Действия"</dd>
        <dt>Редактирование - </dt>
        <dd>клик по кнопке <img src="./img//ed.png"> в колонке "Действия"</dd>
        <dt>Удаление - </dt>
        <dd>клик по кнопке <img src="./img/drop.png"> в колонке "Действия"</dd>
    </dl>
</div>
</div>
<script type="text/javascript">
    'use strict';
    let desc; // направления сортировки колонок
    let col;  // колонки для сортировки
    let typeQuery; // тип запроса в БД
    let id;  // айди товара = айди строки в таблице
    let tab; // таблица, на которой получено событие

    // устанавливаем переменные сортировки таблицы:
    $(document).ready(function() {
        setSort();
    });

    // обработчик клика на заголовке таблицы (сортировка):
    $('th').click(function(event){
        event.target.dataset.sort = (event.target.dataset.sort == "asc") ? "desc" : "asc"; // меняем направление сортировки
        desc = event.currentTarget.dataset.sort;
        col = event.currentTarget.dataset.col;

        // если направление сортировки на кликнутой колонке не указано, выходим без запроса:
        if (desc === undefined) {
            return;
        }

        // собираем прочие направления сортировки по колонкам:
        $('[data-sort*="sc"]').each(function (i, val) {
            if (event.currentTarget != val) {
                desc += ',' + val.dataset.sort;
                col += ',' + val.dataset.col;
            }
        });

        $.post("query.php",
                { typeQuery: "sort", sort: desc, column : col, numQuery: tab},
                function(data, result){
                    setData(data, tab);
                }
        );
    });

    // обработчик клика по таблице:
    $('table').click(function(event){
        tab = $(event.target).parentsUntil('table').last().attr('class');

        // обработчик щелка по колонке с исполненным: изменение статуса "исполнен":
        if (event.target.tagName == 'TD' && (event.target.classList[0] == 'done' || event.target.classList[0] == 'undone')) {
            let done = (event.target.classList[0] == "undone") ? "1" : "0";
            id = event.target.parentNode.id;
            $.post("query.php",
                {typeQuery: "update", id: id, done : done, numQuery: tab, sort: desc, column : col},
                function(data, status) {
                    setData(data, tab);
                }
            );
        }

        if (event.target.tagName == 'IMG' && event.target.parentNode.classList[0] == 'del') {
            id = $(event.target).parentsUntil('tbody').last().attr('id');
            $.post("query.php",
                 {typeQuery: "delete", id : id, numQuery: tab, sort: desc, column : col},
                 function(data, result) {
                     $('tbody.' +  tab).html(data); //тут так, т.к. когда удаляем последннюю, виджет не обновляется :((
                 }
            );
        }

        if (event.target.tagName == 'IMG' && event.target.parentNode.classList[0] == 'edit') {
            id = $(event.target).parentsUntil('tbody').last().attr('id');
            let description = $(event.target).parentsUntil('tbody').last().children(':nth-child(2)').text(); // значение 2 колонки
            $('textarea').val(description);
            $('.title').text("Редактирование дела");
            typeQuery = 'update';
            showModal();
        }

        if (event.target.tagName == 'IMG' && event.target.parentNode.classList[0] == 'delegate') {
            id = $(event.target).parentsUntil('tbody').last().attr('id');
            let val= $(event.target).parent('a').next('select').val();
            $.post("query.php",
                {typeQuery: "update", id : id, numQuery: tab, assigned: val, sort: desc, column : col},
                function(data, result) {
                    setData(data, tab);
                }
            );
        }
    });

    // обработчик элементов, изменяющих статус модального окна (3 штуки - закрыть, добавить, сохранить)
    $('.trigger').click(function(event){
        showModal();
    });

    //
    $('.adder').click(function(event) {
        $('.title').text("Добавление нового дела");
        $('textarea').val('');
        typeQuery = 'create';
        tab='tab1'; // обновляемая таблица
        id = '';
    });

    $('.creater').click(function(event) {
        let value = $('textarea').val();
        $.post("query.php",
            {typeQuery: typeQuery, description: value, id: id , numQuery: tab, sort: desc, column : col},
            function(data, result){
                setData(data, tab);
            }
        );
    });

    function showModal() {
        $('.modal-wrapper').toggleClass('open');
        $('.page-wrapper').toggleClass('blur');
    }
    
    function setSort() {
        desc='';
        col='';
        $('th[data-sort*="sc"]').each(function (i, val) {
            if (desc !== '') {desc += ","; }
            if (col !== '') {col += ","; }
            desc += val.dataset.sort;
            col  += val.dataset.col;
        });
    }

    function setData(data, tabClass) {
        if (data !== 'undefined' && data.length > 0) {
            $('tbody.' + tabClass).html(data);
        }
    }

</script>
</body>
</html>

