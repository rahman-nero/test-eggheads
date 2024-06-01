<?php


/**
 * Реализация из ТЗ
*/
function old() {
    $mysqli = new mysqli("localhost", "my_user", "my_password", "world");
    $id = $_GET['id'];
    $res = $mysqli->query('SELECT * FROM users WHERE u_id='. $id);
    $user = $res->fetch_assoc();
}


/**
 * Отрефакторенный код
 *
 * ! Этот код можно еще сильнее разнести, выделяя в отдельные классы и т.д, но не стал этого делать
 * @throws PDOException
*/
function refactored(): void
{
    // Вместо mysqli используем PDO
    $pdo = new PDO("mysql:host=localhost;dbname=world", "my_user", "my_password");

    // Устанавливаем аттрибуты которые будут выдавать ошибки. Как я помню начиная с PHP 8 это поумолчанию стоиты
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Есть возможность что ID не придет.
    $id = $_GET['id'] ?? null;

    // Проверяем на пустоту
    if ($id === null) {
        throw new RuntimeException('Missing id');
    }

    // Биндим параметры
    $res = $pdo->prepare('SELECT * FROM users WHERE u_id = :id');
    $res->execute(['id' => $id]);

    // Fetch'им результат
    $user = $res->fetch(PDO::FETCH_ASSOC);
}