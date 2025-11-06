<?php
// chatbot_api.php

// === PHẦN 1: CÀI ĐẶT ===
// !!! QUAN TRỌNG: Dán API Key của bạn lấy từ Google AI Studio vào đây
$apiKey = 'AIzaSyBf-DfVl4ldDHbMNSf_I26NqzpPtlv5fdo';

// Đây là đường dẫn (endpoint) của AI
$url = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=' . $apiKey;

// === PHẦN 2: LẤY TIN NHẮN TỪ NGƯỜI DÙNG ===
// Lấy tin nhắn (dạng JSON) mà file JavaScript đã gửi lên
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? 'Xin chào';

// === PHẦN 3: GỬI YÊU CẦU ĐẾN GEMINI ===

// Chuẩn bị dữ liệu theo đúng định dạng JSON mà Google yêu cầu
// === PHẦN 3: GỬI YÊU CẦU ĐẾN GEMINI ===

// Chuẩn bị dữ liệu theo đúng định dạng JSON mà Google yêu cầu
$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $userMessage
                ]
            ]
        ]
    ]
];
$jsonData = json_encode($data);

// Khởi tạo cURL (công cụ của PHP để "gọi" các API)
$ch = curl_init($url);

// Cài đặt các tùy chọn cho cURL
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Tắt kiểm tra SSL (một số host local cần)
curl_setopt($ch, CURLOPT_POST, true);

// Thực thi cURL và lấy kết quả
$response = curl_exec($ch);
$error = curl_error($ch); // Lấy lỗi cURL nếu có
curl_close($ch);

// === PHẦN 4: XỬ LÝ VÀ GỬI LẠI KẾT QUẢ ===

$reply = "Xin lỗi, tôi đang gặp sự cố kết nối với AI. Vui lòng thử lại sau.";

if (!$error) { // Nếu không có lỗi cURL
    $responseObject = json_decode($response);

    // Lấy nội dung văn bản trả lời từ AI
    if (isset($responseObject->candidates[0]->content->parts[0]->text)) {
        $reply = $responseObject->candidates[0]->content->parts[0]->text;
    } else {
        // Ghi lại lỗi để bạn debug nếu API Key sai hoặc có vấn đề
        error_log("Lỗi API Gemini: " . $response);
       $reply = "LỖI THẬT TỪ GOOGLE: " . $response;
    }
} else {
    // Nếu cURL bị lỗi (ví dụ: extension=curl chưa bật)
    error_log("Lỗi cURL: " . $error);
    $reply = "Lỗi kết nối cURL: " . $error;
}

// Trả về câu trả lời cho file JavaScript
header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);

?>