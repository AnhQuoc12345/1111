<?php
// chatbot_api.php - Phiên bản đầy đủ chuẩn Gemini 2.5, log debug, fallback DB
header('Content-Type: application/json; charset=utf-8');
session_start();

// === CẤU HÌNH ===
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
error_reporting(E_ALL);

$DEBUG = true; // đặt false khi production
$logFile = __DIR__ . '/chatbot_debug.log';

// === REQUIRE FILES ===
$dbControllerFile = __DIR__ . '/database/DatabaseController.php';
$productFile = __DIR__ . '/database/Product.php';
if (!file_exists($dbControllerFile) || !file_exists($productFile)) {
    echo json_encode(['reply' => 'LỖI PHP: Không tìm thấy file DatabaseController.php hoặc Product.php.'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once($dbControllerFile);
require_once($productFile);

// Đảm bảo class alias tồn tại
if (!class_exists('DBController') && class_exists('DatabaseController')) {
    class_alias('DatabaseController', 'DBController');
}
if (!class_exists('DBController') || !class_exists('Product')) {
    echo json_encode(['reply' => 'LỖI PHP: Class DBController hoặc Product không tồn tại.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// === KHỞI TẠO DB & PRODUCT ===
// Giả định: Các class này hoạt động
$db = new DBController();
$product = new Product($db);

// === LẤY MESSAGE TỪ FRONTEND ===
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? 'Xin chào');

// === CÀI ĐẶT API ===
$apiKey = 'AIzaSyCK0qYiFELtAeFelz73mZKVCGLLb15DWlo'; 
$model = 'gemini-2.5-flash';
$baseUrl = 'https://generativelanguage.googleapis.com/v1/models/' . $model;

// === Hàm gửi cURL ===
function sendCurlRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        // *** ĐÃ SỬA: Tăng thời gian chờ (timeout) để tránh lỗi 15s ***
        CURLOPT_CONNECTTIMEOUT => 10, // Tăng connect timeout lên 10 giây
        CURLOPT_TIMEOUT => 30         // Tăng tổng thời gian chờ lên 30 giây
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);

    if ($err) {
        return ['ok' => false, 'error' => $err, 'raw' => $response, 'http_code' => $httpCode, 'response' => $decoded];
    }

    // Treat non-2xx as error so caller can fallback / log properly
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => 'HTTP ' . $httpCode, 'raw' => $response, 'http_code' => $httpCode, 'response' => $decoded];
    }

    return ['ok' => true, 'response' => $decoded, 'raw' => $response, 'http_code' => $httpCode];
}

// === Hàm trích text từ response (chuẩn Gemini) ===
function extractTextFromResponse($res) {
    if (!is_array($res)) return null;

    // Cấu trúc chuẩn của Gemini API (generateContent)
    if (!empty($res['candidates'][0]['content']['parts'][0]['text'])) {
        return $res['candidates'][0]['content']['parts'][0]['text'];
    }

    // Fallback nếu có cấu trúc khác
    if (!empty($res['text'])) {
        return $res['text'];
    }
    
    return null;
}

// === Chuẩn bị request CHUẨN GEMINI 2.5 (contents/parts) ===
$data = [
    'contents' => [
        [
            'role' => 'user', 
            'parts' => [     
                ['text' => $userMessage]
            ]
        ]
    ]
];

// Gửi request
// Đã sử dụng endpoint :generateContent đúng chuẩn
$res = sendCurlRequest($baseUrl . ':generateContent?key=' . $apiKey, $data);

// Ghi log debug toàn bộ
if ($DEBUG) {
    $logEntry = [
        'time' => date('c'),
        'userMessage' => $userMessage,
        'request_data' => $data,
        'curl_ok' => $res['ok'] ?? false,
        'curl_http_code' => $res['http_code'] ?? null,
        'response_decoded' => $res['response'] ?? null,
        'raw_response' => $res['raw'] ?? null,
        'curl_error' => $res['error'] ?? null
    ];
    file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
}

// Trích text từ response (kiểm tra ok trước)
$reply = '';
if (!empty($res['ok'])) {
    $rawReply = extractTextFromResponse($res['response'] ?? null);

    if (is_string($rawReply) && strlen(trim($rawReply)) > 0) {
        $reply = $rawReply;
    } else {
        // Nếu extract thất bại
        $reply = 'Xin lỗi, tôi đã nhận được phản hồi từ AI nhưng không thể đọc được nội dung. Vui lòng thử lại.';
        if ($DEBUG) {
            $reply = '[[DEBUG: no text in AI response | http=' . ($res['http_code'] ?? 'unknown') . ']]';
        }
    }
} else {
    // cURL / HTTP lỗi (bao gồm Timeout)
    $errorMsg = $res['error'] ?? 'unknown';
    
    // *** ĐÃ SỬA: Phản hồi thân thiện khi không phải DEBUG ***
    if ($DEBUG) {
        $reply = '[[DEBUG: curl/http error | ' . $errorMsg . ']]';
    } else {
        // Thông báo thân thiện cho người dùng, đặc biệt với lỗi mạng/timeout
        if (strpos($errorMsg, 'timed out') !== false || strpos($errorMsg, 'Connection refused') !== false) {
             $reply = 'Xin lỗi, AI đang bận hoặc mạng gặp vấn đề, vui lòng **thử lại câu hỏi sau ít phút**.';
        } else {
             $reply = 'Xin lỗi, đã xảy ra lỗi kết nối với dịch vụ AI. Vui lòng thử lại sau.';
        }
    }
}

// Trả JSON cho frontend
echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
exit;