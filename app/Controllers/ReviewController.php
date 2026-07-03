<?php
namespace App\Controllers;

use App\Services\ReviewService;

class ReviewController {
    private $service;

    public function __construct() {
        $this->service = new ReviewService();
    }

    public function getReviewsByMovieId($movieId) {
        return $this->service->getReviewsByMovieId($movieId);
    }

    public function getRatingSummary($movieId) {
        return $this->service->getRatingSummary($movieId);
    }
}
