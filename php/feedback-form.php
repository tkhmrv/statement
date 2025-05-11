<?php
$logFile = __DIR__ . '/../logs/telegram.log';
require_once __DIR__ . '/../vendor/autoload.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Внутренняя ошибка: файл автозагрузки не найден!"]);
    exit;
}

if (!file_exists($logFile)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Внутренняя ошибка: файл лога Telegram не найден!"]);
    exit;
}
require_once $autoloadPath;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');

function logToTelegramFile($message)
{
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    $entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса!']);
    exit;
}

function verifyTurnstile($token)
{
    $secretKey = $_ENV['TURNSTILE_SECRET_KEY'];
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success ?? false;
}

if (!isset($_POST['cf-turnstile-response']) || !verifyTurnstile($_POST['cf-turnstile-response'])) {
    echo json_encode(['success' => false, 'message' => 'Проверка CAPTCHA не пройдена!']);
    exit;
}

// Получаем поля
$name = trim($_POST['name'] ?? '');
$field = trim($_POST['field'] ?? ''); // это IdMessenger: 1 или 2
$phone = trim($_POST['phone'] ?? '');
$topic = trim($_POST['topic'] ?? '');
$message = trim($_POST['message'] ?? '');
$agree = isset($_POST['privacy-policy']);

// Очищаем телефон: оставляем только цифры и +
$cleanPhone = preg_replace('/[^\d+]/', '', $phone);

// Валидация полей по отдельности
if (empty($name) || mb_strlen($name) < 3 || !preg_match('/^[A-Za-zА-Яа-яЁё\s]+$/u', $name)) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, введите корректное имя!']);
    exit;
}

if (!in_array($field, ['1', '2'])) {
    echo json_encode(['success' => false, 'message' => 'Выберите мессенджер для связи!']);
    exit;
}

if (empty($cleanPhone) || !preg_match('/^\+7\d{10}$/', $cleanPhone)) {
    echo json_encode(['success' => false, 'message' => 'Введите корректный номер телефона!']);
    exit;
}

if (empty($topic) || mb_strlen($topic) < 3) {
    echo json_encode(['success' => false, 'message' => 'Заполните тему обращения!']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Введите текст сообщения!']);
    exit;
}

if (!$agree) {
    echo json_encode(['success' => false, 'message' => 'Необходимо согласие с политикой конфиденциальности!']);
    exit;
}

// Подключение к БД
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Вставка
    $stmt = $pdo->prepare("INSERT INTO FeedbackForm (Name, Phone, MessengerId, Topic, Message, PrivacyCheckbox) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $cleanPhone, $field, $topic, $message, true]);

    // Telegram section
    // Получаем текстовое название мессенджера
    $messengerMap = ['1' => 'Telegram', '2' => 'WhatsApp'];
    $messengerText = $messengerMap[$field] ?? 'Неизвестно';

    // Формируем сообщение
    $tgMessage = "📬 Новое обращение с сайта:\n\n";
    $tgMessage .= "👤 Имя: $name\n";
    $tgMessage .= "📱 Телефон: $cleanPhone\n";
    $tgMessage .= "💬 Мессенджер: $messengerText\n";
    $tgMessage .= "📌 Тема обращения: $topic\n";
    $tgMessage .= "📝 Сообщение:\n$message\n";
    $tgMessage .= "✅ Согласие с политикой: Да\n\n";
    $tgMessage .= "🔗 Быстрые ссылки:\n";
    $tgMessage .= "Telegram: https://t.me/$cleanPhone\n";
    $tgMessage .= "WhatsApp: https://wa.me/" . ltrim($cleanPhone, '+') . "\n";

    // Telegram bot
    $botToken = $_ENV['BOT_TOKEN'];
    $tgApiUrl = "https://api.telegram.org/bot$botToken/sendMessage";

    // Получаем все chat_id из БД
    try {
        $chatQuery = $pdo->query("SELECT IdTgChat, ChatId, ChatTitle FROM TgChats");
        $chatRows = $chatQuery->fetchAll(PDO::FETCH_ASSOC);

        $deletedChats = [];

        foreach ($chatRows as $row) {
            $chatId = $row['ChatId'];
            $chatDbId = $row['IdTgChat'];
            $chatTitle = $row['ChatTitle'] ?? 'Без названия';

            $postData = [
                'chat_id' => $chatId,
                'text' => $tgMessage,
                'parse_mode' => 'HTML'
            ];

            $ch = curl_init($tgApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                // Успешная отправка
                $updateSuccess = $pdo->prepare("UPDATE TgChats SET LastMessageSuccessAt = NOW(), ErrorCount = 0 WHERE IdTgChat = ?");
                $updateSuccess->execute([$chatDbId]);
                logToTelegramFile("✅ Успешно отправлено в чат {$chatId} ({$chatTitle})");
            } elseif (in_array($httpCode, [400, 403])) {
                // Ошибка доступа – инкрементируем ErrorCount
                $pdo->prepare("UPDATE TgChats SET ErrorCount = ErrorCount + 1 WHERE IdTgChat = ?")->execute([$chatDbId]);

                logToTelegramFile("⚠️ Ошибка $httpCode при отправке в чат {$chatId} ({$chatTitle}). Увеличен ErrorCount.");

                // Проверяем ErrorCount
                $countStmt = $pdo->prepare("SELECT ErrorCount FROM TgChats WHERE IdTgChat = ?");
                $countStmt->execute([$chatDbId]);
                $errorCount = $countStmt->fetchColumn();

                if ($errorCount > 3) {
                    // Удалить чат из БД
                    $pdo->prepare("DELETE FROM TgChats WHERE IdTgChat = ?")->execute([$chatDbId]);
                    $deletedChats[] = [
                        'id' => $chatId,
                        'title' => $chatTitle
                    ];
                    logToTelegramFile("❌ Чат {$chatId} ({$chatTitle}) удалён из базы после более 3 ошибок.");
                }
            }
        }

        // Отправляем уведомление во все оставшиеся чаты об удалении
        if (!empty($deletedChats)) {
            $remainingChats = $pdo->query("SELECT ChatId FROM TgChats")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($deletedChats as $deleted) {
                $deleteMsg = "⚠️ Чат был удалён из списка рассылки:\n\n";
                $deleteMsg .= "Chat ID: <code>{$deleted['id']}</code>\n\n";
                $deleteMsg .= "Название: {$deleted['title']}\n\n";
                $deleteMsg .= "Причина: чат неактуален, не существует или бот был из него удален.";

                foreach ($remainingChats as $chatId) {
                    $postData = [
                        'chat_id' => $chatId,
                        'text' => $deleteMsg,
                        'parse_mode' => 'HTML'
                    ];

                    $ch = curl_init($tgApiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_exec($ch);
                    curl_close($ch);

                    logToTelegramFile("ℹ️ Уведомление об удалении чата {$deleted['id']} отправлено в чат {$chatId}");
                }
            }
        }

    } catch (Exception $e) {
        $errorMsg = "Ошибка при работе с Telegram-чатами: " . $e->getMessage();
        error_log($errorMsg);
        logToTelegramFile("🔥 $errorMsg");
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении в базу данных!']);
}
