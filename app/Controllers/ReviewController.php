<?php
namespace App\Controllers;

use App\Services\ReviewService;

class ReviewController {
    private $reviewService;

    public function __construct() {
        $this->reviewService = new ReviewService();
    }

    public function handleRequest() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add_review') {
                if (!isset($_SESSION['user']['id'])) {
                    return ['status' => 'error', 'message' => 'Vui lòng đăng nhập để bình luận.'];
                }
                
                $userId = $_SESSION['user']['id'];
                $movieId = (int)($_POST['movie_id'] ?? 0);
                $rating = (int)($_POST['rating'] ?? 5);
                $comment = $_POST['comment'] ?? '';

                return $this->reviewService->addReview($userId, $movieId, $rating, $comment);
            }
        }
        return null;
    }

    public function getReviewsByMovie($movieId) {
        return $this->reviewService->getReviewsByMovie($movieId);
    }
}
