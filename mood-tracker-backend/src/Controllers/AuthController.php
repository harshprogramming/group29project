<?php

class AuthController
{
    public function __construct(
        private AuthService $authService,
        private UserRepository $userRepository
    ) {}

    public function register(): void
    {
        $result = $this->authService->register(Request::body());

        if (isset($result['errors'])) {
            Response::error('Validation failed', 422, $result['errors']);
        }

        Response::success($result, 'User registered successfully', 201);
    }

    public function login(): void
    {
        $result = $this->authService->login(Request::body());

        if (isset($result['errors'])) {
            Response::error('Login failed', 401, $result['errors']);
        }

        Response::success($result, 'Login successful');
    }

    public function logout(): void
    {
        $this->authService->logout();
        Response::success([], 'Logged out successfully');
    }

    public function me(): void
    {
        $userId = current_user_id();

        if (!$userId) {
            Response::error('Unauthorized', 401);
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            Response::error('User not found', 404);
        }

        Response::success(['user' => $user], 'Current user fetched');
    }
}