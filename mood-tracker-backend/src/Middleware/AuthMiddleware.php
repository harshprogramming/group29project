<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Response::error('Unauthorized', 401);
        }
    }
}