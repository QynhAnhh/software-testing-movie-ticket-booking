<?php

namespace App\Config;

use RuntimeException;

class DatabaseException extends RuntimeException
{
}

class Database
{
    private static $connection = null;
    private static $config = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            $config = self::loadConfig();

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

    /**
     * Load database configuration once and cache it.
     * Ensures the returned value is an array and avoids require_once pitfalls.
     *
     * @return array
     * @throws DatabaseException
     */
    private static function loadConfig()
    {
        if (self::$config === null) {
            $file = __DIR__ . '/database.local.php';
            $alt = __DIR__ . '/database.php';

            if (file_exists($file)) {
                $cfg = require $file;
            } elseif (file_exists($alt)) {
                $cfg = require $alt;
            } else {
                throw new DatabaseException(
                    'Thiếu cấu hình database.local.php. '
                    . 'Hãy sao chép từ database.local.example.php.'
                );
            }

            if (!is_array($cfg)) {
                throw new DatabaseException('File cấu hình database phải trả về một mảng.');
            }

            self::$config = $cfg;
        }

        return self::$config;
    }
}