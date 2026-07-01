<?php
namespace App\Config;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            $host = 'localhost';
            $username = 'root';
            $password = ''; // Mặc định của XAMPP là rỗng
            $database = 'movie_ticket_booking';
            $port = '3308'; // Mặc định của MySQL là 3306

            $conn = mysqli_connect($host, $username, $password, $database, $port);
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            mysqli_set_charset($conn, "utf8mb4");
            self::$connection = $conn;
        }
        return self::$connection;
    }
}
