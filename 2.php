<?php


/**
 * Проблемы:
 * 1. Нет именованных параметров, поэтому возможна SQL-инъекция
 *
 * 2. Нет lazy load пользователей, при каждом итерации будет запрос к базе, проблема N+1.
 *
 * 3. В худшем случае будет ситуация что у всех вопросов один и тот же автор, в таком случае будет выполняться один
 * и тот же запрос.
 *
 * 4. Также у меня вопрос появился по функции free. В PHP же есть copy on write, когда полное копирование массива идет если на нем какие-то действия выполняют.
 * Вопрос, если мы присвоим результат в $result и сделаем free, во время очищения данных из памяти, не затронется ли это и $result массива?.
 * Нужно с memory_get_usage проверить это, не уверен, давно нативно не работал
 */
function old()
{
    $questionsQ = $mysqli->query('SELECT * FROM questions WHERE catalog_id=' . $catId);
    $result = array();
    while ($question = $questionsQ->fetch_assoc()) {
        $userQ = $mysqli->query('SELECT name, gender FROM users WHERE id=' . (int)$question[‘user_id’]);
        $user = $userQ->fetch_assoc();
        $result[] = array('question' => $question, 'user' => $user);
        $userQ->free();
    }
    $questionsQ->free();
}


/**
 * Переводим на PDO, решаем проблемы с SQL-инъекцией и N+1 проблему, используем lazy load
 */
function refactored()
{
    // Вместо mysqli используем PDO
    $pdo = new PDO("mysql:host=localhost;dbname=world", "my_user", "my_password");

    // Устанавливаем аттрибуты которые будут выдавать ошибки. Как я помню начиная с PHP 8 это поумолчанию стоиты
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Биндим параметры
    $question_stmt = $pdo->prepare('SELECT * FROM questions WHERE catalog_id = :catalog_id');
    $question_stmt->execute(['catalog_id' => $catId]);

    // Fetch'им результат
    $questions = $question_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем ID все пользователей для lazy load
    $user_ids = array_values(
        array_unique(
            array_column($questions, 'user_id')
        )
    );

    // Пользователи которые будем загружать (Lazy load)
    $users = [];

    // Проверяем есть ли пользователи для загрузки
    if (!empty($user_ids)) {
        // Тут биндить не получится
        $users_stmt = $pdo->query('SELECT name, gender FROM users WHERE id IN (' . implode(',', $user_ids) . ')');

        // Получение всех пользователей
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $result = array();

    // Перебираем все вопросы
    foreach ($questions as $question) {

        // Пользователь который относится к вопросу
        $user = null;

        // Если user_id не равняется null
        if (!empty($question['user_id'])) {
            // Перебираем пользователя и ищем подходящего
            $user = array_filter($users, function ($user) use ($question) {
                return $question['user_id'] === $user['id'];
            });
        }

        // Можно генераторы (yield) использовать по сути, для сохранения памяти
        $result[] = [
            'question' => $question,
            'user'     => $user,
        ];
    }

    // Аналога функции free не было, поэтому решил закрыть соединение PDO, для этого сказали просто убрать ссылку на PDO, чтобы garbage collector очистил.
    $pdo = null;
}
