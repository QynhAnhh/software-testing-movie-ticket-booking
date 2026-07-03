<?php
namespace App\Services;

use App\Models\ReviewModel;

class ReviewService {
    private $model;

    public function __construct() {
        $this->model = new ReviewModel();
    }

    public function getReviewsByMovieId($movieId) {
        $movieId = (int)$movieId;
        if ($movieId <= 0) {
            return [];
        }
        return $this->model->getByMovieId($movieId);
    }

    public function getRatingSummary($movieId) {
        $movieId = (int)$movieId;
        if ($movieId <= 0) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0
            ];
        }
        return $this->model->getRatingSummary($movieId);
    }
}
