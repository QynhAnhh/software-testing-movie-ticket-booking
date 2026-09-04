<?php
$cases = [
    // Register
    ['id'=>'TC-AH-01', 'name'=>'Đăng ký hợp lệ (13 ký tự)', 'action'=>'register', 'payload'=>['first_name'=>'Nguyễn', 'last_name'=>'A', 'email'=>'test@test.com', 'password'=>'abcdfhgjtghtg', 'confirm_password'=>'abcdfhgjtghtg', 'phone'=>'0901234567'], 'expect'=>'success', 'msg'=>''],
    ['id'=>'TC-AH-02', 'name'=>'Mật khẩu quá ngắn (3 ký tự)', 'action'=>'register', 'payload'=>['email'=>'test2@test.com', 'password'=>'abc', 'confirm_password'=>'abc'], 'expect'=>'error', 'msg'=>'Mật khẩu phải từ 6-20 ký tự'],
    ['id'=>'TC-AH-03', 'name'=>'Mật khẩu quá dài (25 ký tự)', 'action'=>'register', 'payload'=>['email'=>'test3@test.com', 'password'=>'abcdefghijklmnopqrstuvwxy', 'confirm_password'=>'abcdefghijklmnopqrstuvwxy'], 'expect'=>'error', 'msg'=>'Mật khẩu không được vượt quá 20 ký tự'],
    ['id'=>'TC-AH-04', 'name'=>'Mật khẩu 5 ký tự (Dưới biên)', 'action'=>'register', 'payload'=>['email'=>'test4@test.com', 'password'=>'abcde', 'confirm_password'=>'abcde'], 'expect'=>'error', 'msg'=>'Mật khẩu phải từ 6-20 ký tự'],
    ['id'=>'TC-AH-05', 'name'=>'Mật khẩu 6 ký tự (Ngay biên)', 'action'=>'register', 'payload'=>['email'=>'test5@test.com', 'password'=>'abcdef', 'confirm_password'=>'abcdef'], 'expect'=>'success', 'msg'=>''],
    ['id'=>'TC-AH-06', 'name'=>'Mật khẩu 7 ký tự (Trên biên dưới)', 'action'=>'register', 'payload'=>['email'=>'test6@test.com', 'password'=>'abcdefg', 'confirm_password'=>'abcdefg'], 'expect'=>'success', 'msg'=>''],
    ['id'=>'TC-AH-07', 'name'=>'Mật khẩu 19 ký tự (Dưới biên trên)', 'action'=>'register', 'payload'=>['email'=>'test7@test.com', 'password'=>'abcdefghijklmnopqrs', 'confirm_password'=>'abcdefghijklmnopqrs'], 'expect'=>'success', 'msg'=>''],
    ['id'=>'TC-AH-08', 'name'=>'Mật khẩu 20 ký tự (Ngay biên trên)', 'action'=>'register', 'payload'=>['email'=>'test8@test.com', 'password'=>'abcdefghijklmnopqrst', 'confirm_password'=>'abcdefghijklmnopqrst'], 'expect'=>'success', 'msg'=>''],
    ['id'=>'TC-AH-09', 'name'=>'Mật khẩu 21 ký tự (Trên biên trên)', 'action'=>'register', 'payload'=>['email'=>'test9@test.com', 'password'=>'abcdefghijklmnopqrstu', 'confirm_password'=>'abcdefghijklmnopqrstu'], 'expect'=>'error', 'msg'=>'Mật khẩu không được vượt quá 20 ký tự'],
    ['id'=>'TC-AH-11', 'name'=>'SĐT chứa chữ cái', 'action'=>'register', 'payload'=>['email'=>'test11@test.com', 'phone'=>'0901a14d67'], 'expect'=>'error', 'msg'=>'Số điện thoại chỉ được chứa số'],
    ['id'=>'TC-AH-12', 'name'=>'SĐT ít hơn 10 chữ số', 'action'=>'register', 'payload'=>['email'=>'test12@test.com', 'phone'=>'012345678'], 'expect'=>'error', 'msg'=>'Số điện thoại phải bao gồm 10 số'],
    ['id'=>'TC-AH-13', 'name'=>'SĐT nhiều hơn 10 chữ số', 'action'=>'register', 'payload'=>['email'=>'test13@test.com', 'phone'=>'01234567890'], 'expect'=>'error', 'msg'=>'Số điện thoại phải bao gồm 10 số'],
    ['id'=>'TC-AH-14', 'name'=>'SĐT không bắt đầu bằng số 0', 'action'=>'register', 'payload'=>['email'=>'test14@test.com', 'phone'=>'1234567890'], 'expect'=>'error', 'msg'=>'Số điện thoại phải bắt đầu bằng số 0'],
    ['id'=>'TC-AH-15', 'name'=>'Đăng ký trùng Email', 'action'=>'register', 'payload'=>['email'=>'exist@test.com'], 'expect'=>'error', 'msg'=>'Email này đã được sử dụng'],
    ['id'=>'TC-AH-16', 'name'=>'Đăng ký trùng SĐT', 'action'=>'register', 'payload'=>['email'=>'test16@test.com', 'phone'=>'0922222222'], 'expect'=>'error', 'msg'=>'Số điện thoại này đã được sử dụng'],
    
    // Login
    ['id'=>'TC-AH-17', 'name'=>'Đăng nhập hợp lệ', 'action'=>'login', 'payload'=>['email'=>'exist@test.com', 'password'=>'123456'], 'expect'=>'success', 'msg'=>''],
    ['id'=>'TC-AH-18', 'name'=>'Đăng nhập sai mật khẩu', 'action'=>'login', 'payload'=>['email'=>'exist@test.com', 'password'=>'wrongpass'], 'expect'=>'error', 'msg'=>'Email hoặc mật khẩu không đúng'],
    ['id'=>'TC-AH-19', 'name'=>'Đăng nhập Email chưa đăng ký', 'action'=>'login', 'payload'=>['email'=>'notfound@test.com', 'password'=>'123456'], 'expect'=>'error', 'msg'=>'Email hoặc mật khẩu không đúng'],
    ['id'=>'TC-AH-20', 'name'=>'Đăng nhập để trống', 'action'=>'login', 'payload'=>['email'=>'', 'password'=>''], 'expect'=>'error', 'msg'=>'Vui lòng nhập email và mật khẩu'],
    
    // Register Part 2
    ['id'=>'TC-AH-21', 'name'=>'Xác nhận mật khẩu không khớp', 'action'=>'register', 'payload'=>['email'=>'test21@test.com', 'password'=>'123456', 'confirm_password'=>'123457'], 'expect'=>'error', 'msg'=>'Xác nhận mật khẩu không khớp'],
    ['id'=>'TC-AH-22', 'name'=>'Đăng ký để trống tất cả', 'action'=>'register', 'payload'=>['first_name'=>'', 'last_name'=>'', 'email'=>'', 'phone'=>'', 'password'=>'', 'confirm_password'=>''], 'expect'=>'error', 'msg'=>'Vui lòng nhập đầy đủ thông tin'],
    ['id'=>'TC-AH-23', 'name'=>'Đăng ký để trống 1 trường', 'action'=>'register', 'payload'=>['first_name'=>'Nguyễn', 'last_name'=>'A', 'phone'=>'0901234567', 'password'=>'123456', 'confirm_password'=>'123456', 'email'=>''], 'expect'=>'error', 'msg'=>'Vui lòng nhập đầy đủ thông tin'],
    ['id'=>'TC-AH-24', 'name'=>'Email sai định dạng (Thiếu @)', 'action'=>'register', 'payload'=>['email'=>'test24test.com'], 'expect'=>'error', 'msg'=>'Email không hợp lệ'],
    ['id'=>'TC-AH-25', 'name'=>'Email sai định dạng (Thiếu tên miền)', 'action'=>'register', 'payload'=>['email'=>'test24@'], 'expect'=>'error', 'msg'=>'Email không hợp lệ'],
    ['id'=>'TC-AH-26', 'name'=>'Họ chứa số', 'action'=>'register', 'payload'=>['first_name'=>'Nguyễn 123', 'email'=>'test26@test.com'], 'expect'=>'error', 'msg'=>'Họ không được chứa số hoặc ký tự đặc biệt'],
    ['id'=>'TC-AH-27', 'name'=>'SĐT chứa ký tự đặc biệt', 'action'=>'register', 'payload'=>['email'=>'test27@test.com', 'phone'=>'0901234567@'], 'expect'=>'error', 'msg'=>'Số điện thoại không hợp lệ'],
    ['id'=>'TC-AH-28', 'name'=>'Tên có ký tự đặc biệt', 'action'=>'register', 'payload'=>['first_name'=>'Nguyễn', 'last_name'=>'A@', 'email'=>'test28@test.com'], 'expect'=>'error', 'msg'=>'Tên không được chứa số hoặc ký tự đặc biệt'],
    ['id'=>'TC-AH-29', 'name'=>'Bỏ trống tên', 'action'=>'register', 'payload'=>['first_name'=>'Nguyễn', 'last_name'=>'', 'email'=>'test29@test.com'], 'expect'=>'error', 'msg'=>'Vui lòng nhập đầy đủ thông tin'],
    ['id'=>'TC-AH-30', 'name'=>'Tên vượt quá 255 ký tự', 'action'=>'register', 'payload'=>['last_name'=>str_repeat('a', 256), 'email'=>'test30@test.com'], 'expect'=>'error', 'msg'=>'Tên không được vượt quá 255 ký tự'],
    ['id'=>'TC-AH-31', 'name'=>'Bỏ trống Họ', 'action'=>'register', 'payload'=>['first_name'=>'', 'last_name'=>'A', 'email'=>'test31@test.com'], 'expect'=>'error', 'msg'=>'Vui lòng nhập đầy đủ thông tin'],
    ['id'=>'TC-AH-32', 'name'=>'Họ vượt quá 255 ký tự', 'action'=>'register', 'payload'=>['first_name'=>str_repeat('a', 256), 'last_name'=>'A', 'email'=>'test32@test.com'], 'expect'=>'error', 'msg'=>'Họ không được vượt quá 255 ký tự'],
];

$items = [];
foreach ($cases as $case) {
    // Merge with base payload to ensure fields exist
    $basePayload = [
        'first_name' => 'Nguyễn',
        'last_name' => 'A',
        'email' => 'test@test.com',
        'phone' => '0901234567',
        'password' => '123456',
        'confirm_password' => '123456'
    ];
    
    if ($case['action'] === 'login') {
        $basePayload = ['email' => 'exist@test.com', 'password' => '123456'];
    }
    
    $payload = array_merge($basePayload, $case['payload']);

    $testScript = [
        "pm.test(\"[{$case['id']}] {$case['name']}\", function () {",
        "    var jsonData = pm.response.json();",
    ];

    if ($case['expect'] === 'success') {
        $testScript[] = "    pm.expect(jsonData.status).to.eql('success');";
    } else {
        $testScript[] = "    pm.expect(jsonData.status).to.eql('error');";
        if (!empty($case['msg'])) {
            $testScript[] = "    pm.expect(jsonData.message).to.include('{$case['msg']}');";
        }
    }
    $testScript[] = "});";

    $item = [
        'name' => "[{$case['id']}] {$case['name']}",
        'event' => [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => $testScript,
                    'type' => 'text/javascript'
                ]
            ]
        ],
        'request' => [
            'method' => 'POST',
            'header' => [
                ['key' => 'Content-Type', 'value' => 'application/json']
            ],
            'body' => [
                'mode' => 'raw',
                'raw' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ],
            'url' => [
                'raw' => '{{base_url}}/api/auth.php?action=' . $case['action'],
                'host' => ['{{base_url}}'],
                'path' => ['api', 'auth.php'],
                'query' => [
                    ['key' => 'action', 'value' => $case['action']]
                ]
            ]
        ]
    ];
    $items[] = $item;
}

$collection = [
    'info' => [
        'name' => 'Movie Ticket Booking - Auth (32 Test Cases)',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'item' => $items,
    'variable' => [
        [
            'key' => 'base_url',
            'value' => 'http://localhost/software-testing-movie-ticket-booking'
        ]
    ]
];

file_put_contents('movie_ticket_auth_test.postman_collection.json', json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Collection generated successfully!\n";
