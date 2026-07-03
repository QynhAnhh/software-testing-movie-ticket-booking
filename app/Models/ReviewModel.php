<?php

namespace App\Models;

use App\Config\Database;


class ReviewModel {

    private $conn;


    public function __construct() {

        $this->conn =
            Database::getConnection();
    }



    public function getByMovieId($movieId) {


        $stmt = mysqli_prepare(
            $this->conn,
            "
            SELECT
                r.*,
                u.first_name,
                u.last_name

            FROM reviews r

            INNER JOIN users u
                ON u.id = r.user_id

            WHERE movie_id = ?

            ORDER BY created_at DESC
            "
        );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $movieId
        );


        mysqli_stmt_execute($stmt);


        $result =
            mysqli_stmt_get_result($stmt);


        $reviews=[];


        while($row=mysqli_fetch_assoc($result)){

            $reviews[]=$row;
        }


        return $reviews;
    }




    public function getRatingSummary($movieId) {


        $stmt=mysqli_prepare(
            $this->conn,
            "
            SELECT
                ROUND(AVG(rating),1)
                    average_rating,

                COUNT(*)
                    total_reviews

            FROM reviews

            WHERE movie_id=?
            "
        );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $movieId
        );


        mysqli_stmt_execute($stmt);


        $result =
            mysqli_stmt_get_result($stmt);


        $row =
            mysqli_fetch_assoc($result);



        return [
            'average_rating'
                =>
                $row['average_rating'] ?? 0,

            'total_reviews'
                =>
                $row['total_reviews'] ?? 0
        ];
    }




    public function create($data) {


        $stmt=mysqli_prepare(
            $this->conn,
            "
            INSERT INTO reviews
            (
            user_id,
            movie_id,
            rating,
            comment
            )

            VALUES (?,?,?,?)
            "
        );


        mysqli_stmt_bind_param(
            $stmt,
            "iiis",
            $data['user_id'],
            $data['movie_id'],
            $data['rating'],
            $data['comment']
        );


        return mysqli_stmt_execute($stmt);
    }



    public function getError(){

        return mysqli_error($this->conn);
    }
}