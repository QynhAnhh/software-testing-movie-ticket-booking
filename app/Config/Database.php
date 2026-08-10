<?php

namespace App\Config;

use RuntimeException;

class DatabaseException extends RuntimeException
{
}

class Database
{
    private static $connection = null;

    private static $configCache = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            $configFile = __DIR__ . '/database.local.php';

            if (!is_file($configFile)) {
                throw new DatabaseException(
                    'Thiếu cấu hình database.local.php. '
                    . 'Hãy sao chép từ database.local.example.php.'
                );
            }

            if (self::$configCache === null) {
                self::$configCache = require_once $configFile;
            }
            $config = self::$configCache;

            $requiredKeys = [
                'host',
                'username',
                'password',
                'database',
                'port',
            ];

            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $config)) {
                    throw new DatabaseException(
                        "Thiếu cấu hình database: {$key}"
                    );
                }
            }

            if (
                !is_string($config['password'])
                || trim($config['password']) === ''
            ) {
                throw new DatabaseException(
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
                throw new DatabaseException(
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