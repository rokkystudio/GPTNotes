<?php
// Простой API для управления кешем заметок (mygptnotes)

// echo hash('sha256', 'mygptnotes');

// === Константы ===
// Хеш доступа (sha256)
const ACCESS_HASH = 'cba76dee61cf6d8093c68641557b9dad57272b8e4949a30bec06fa1be804593a';

// === Установка заголовков ===
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

// === Проверка ключа доступа ===
$key = $_GET['key'] ?? ($_POST['key'] ?? '');
if (hash('sha256', $key) !== ACCESS_HASH) {
    jsonResponse(['error' => 'unauthorized'], 403);
}

// === Параметры базы данных ===
const DB_HOST = 'localhost';
const DB_NAME = 'gptnotes';
const DB_USER = 'root';
const DB_PASS = 'Tatsu1898286';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Ошибка подключения к базе данных: ' . $e->getMessage()], 500);
}

// === Получение параметров ===
$cmd = $_GET['cmd'] ?? ($_POST['cmd'] ?? '');
$idRaw = $_GET['id'] ?? ($_POST['id'] ?? '');
$text = $_GET['text'] ?? ($_POST['text'] ?? '');
$id = mb_strtolower(trim($idRaw));

if (in_array($cmd, ['add', 'replace', 'finish', 'reset']) && $id === '') {
    jsonResponse(['error' => 'Отсутствует параметр id (идентификатор заметки)']);
}

// === Работа с кэшем ===
function getCacheNote(PDO $pdo, string $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM cache WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function addCacheNote(PDO $pdo, string $id, string $cmd, string $text): bool {
    $existing = getCacheNote($pdo, $id);
    if ($existing) {
        $newText = $existing['text'] . $text;
        $stmt = $pdo->prepare('UPDATE cache SET cmd = ?, text = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$cmd, $newText, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cache (id, cmd, text) VALUES (?, ?, ?)');
        return $stmt->execute([$id, $cmd, $text]);
    }
}

function delCacheNote(PDO $pdo, string $id): bool {
    $stmt = $pdo->prepare('DELETE FROM cache WHERE id = ?');
    return $stmt->execute([$id]);
}

// === Обработка команд ===
function cmdUpdate(PDO $pdo, string $id, string $cmd, string $text): void {
    if (addCacheNote($pdo, $id, $cmd, $text)) {
        jsonResponse(['status' => 'ok', 'message' => 'Заметка обновлена (' . $cmd . ')']);
    } else {
        jsonResponse(['error' => 'Не удалось обновить заметку']);
    }
}

function cmdFinish(PDO $pdo, string $id): void {
    $cache = getCacheNote($pdo, $id);
    if (!$cache) jsonResponse(['error' => 'Заметка с указанным id не найдена']);

    $cmd = $cache['cmd'];
    $text = $cache['text'];

    if (in_array($cmd, ['add', 'replace'])) {
        delCacheNote($pdo, $id);
        jsonResponse([
            'status' => 'ok',
            'message' => 'Заметка ' . $cmd . ' завершена. Результат передан.',
            'text' => $text
        ]);
    } else {
        jsonResponse(['error' => 'Недопустимая операция finish']);
    }
}

function cmdReset(PDO $pdo, string $id): void {
    delCacheNote($pdo, $id) ?
        jsonResponse(['status' => 'ok', 'message' => 'Заметка сброшена']) :
        jsonResponse(['error' => 'Не удалось сбросить заметку']);
}

function cmdGetAll(PDO $pdo): void {
    $stmt = $pdo->query('SELECT * FROM cache ORDER BY updated_at DESC');
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['status' => 'ok', 'cache' => $all]);
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// === Обработка запроса ===
switch ($cmd) {
    case 'add':
    case 'replace':
        cmdUpdate($pdo, $id, $cmd, $text);
        break;

    case 'reset':
        cmdReset($pdo, $id);
        break;

    case 'finish':
        cmdFinish($pdo, $id);
        break;

    case 'getall':
        cmdGetAll($pdo);
        break;

    default:
        jsonResponse(['error' => 'Неизвестная команда']);
}
