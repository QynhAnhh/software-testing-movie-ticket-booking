<?php

namespace App\Services;

use App\Models\ReviewModel;
use App\Models\MovieModel;


class ReviewService {

    private $model;
    private $movieModel;


    public function __construct() {

        $this->model =
            new ReviewModel();


        $this->movieModel =
            new MovieModel();
    }



    public function addReview(
        $userId,
        $movieId,
        $rating,
        $comment
    ) {


        if ($userId <= 0) {

            return [
                'status'=>'error',
                'message'=>'Vui lòng đăng nhập'
            ];
        }



        if ($movieId <= 0) {

            return [
                'status'=>'error',
                'message'=>'Phim không hợp lệ'
            ];
        }



        if ($rating < 1 || $rating > 5) {

            return [
                'status'=>'error',
                'message'=>'Đánh giá từ 1-5 sao'
            ];
        }



        if (trim($comment) === '') {

            return [
                'status'=>'error',
                'message'=>'Nội dung trống'
            ];
        }



        $success =
            $this->model->create([
                'user_id'=>$userId,
                'movie_id'=>$movieId,
                'rating'=>$rating,
                'comment'=>$comment
            ]);



        if ($success) {

            return [
                'status'=>'success',
                'message'=>'Đánh giá thành công'
            ];
        }


        return [
            'status'=>'error',
            'message'=>$this->model->getError()
        ];
    }




    public function getReviewsByMovieId($movieId) {

        if ($movieId <= 0) {
            return [];
        }


        return $this->model
            ->getByMovieId($movieId);
    }




    public function getRatingSummary($movieId) {

        if ($movieId <= 0) {

            return [
                'average_rating'=>0,
                'total_reviews'=>0
            ];
        }


        return $this->model
            ->getRatingSummary($movieId);
    }
}