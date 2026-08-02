<?php

namespace App\Config;

use RuntimeException;

class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            $configFile = __DIR__ . '/database.local.php';

            if (!is_file($configFile)) {
                throw new RuntimeException(
                    'Thiếu cấu hình database.local.php. '
                    . 'Hãy sao chép từ database.local.example.php.'
                );
            }

            $config = require $configFile;

            $requiredKeys = [
                'host',
                'username',
                'password',
                'database',
                'port',
            ];

            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $config)) {
                    throw new RuntimeException(
                        "Thiếu cấu hình database: {$key}"
                    );
                }
            }

            if (
                !is_string($config['password'])
                || trim($config['password']) === ''
            ) {
                throw new RuntimeException(
                    'Mật khẩu database không được để trống.'
                );
            }

            $conn = mysqli_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                (int) $config['port']
            );

            if (!$conn) {
                throw new RuntimeException(
                    'Database connection failed: '
                    . mysqli_connect_error()
                );
            }

            mysqli_set_charset($conn, 'utf8mb4');
            self::$connection = $conn;
        }

        return self::$connection;
    }
}