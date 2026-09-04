<?php

require_once __DIR__ . '/init.php';

use App\Controllers\BookingController;


$controller = new BookingController();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    sendJsonResponse(
        'error',
        'Chỉ hỗ trợ POST',
        null,
        405
    );

}


$inputJSON = file_get_contents('php://input');

$inputData = json_decode($inputJSON, true);


if (is_array($inputData)) {

    $_POST = array_merge($_POST, $inputData);

}


$result = $controller->handleRequest();


if ($result) {

    sendJsonResponse(
        $result['status'],
        $result['message'],
        $result
    );

}
else {

    sendJsonResponse(
        'error',
        'Request không hợp lệ',
        null,
        400
    );

}