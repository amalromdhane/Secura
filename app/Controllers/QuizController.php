<?php
namespace App\Controllers;

class QuizController
{
    public function form(): void
    {
        // Quiz display handled by existing public/quiz.php
        require __DIR__ . '/../public/quiz.php';
    }

    public function submit(): void
    {
        // Quiz scoring still in public/quiz.php
        // This endpoint is here for future separation
        require __DIR__ . '/../public/quiz.php';
    }
}
