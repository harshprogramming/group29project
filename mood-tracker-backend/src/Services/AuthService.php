<?php

class AuthService
{
    public function __construct(private UserRepository $userRepository) {}

    public function register(array $data): array
    {
        $errors = validate_required($data, ['name', 'email', 'password']);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['errors' => ['email' => 'Invalid email format.']];
        }

        if (strlen($data['password']) < 6) {
            return ['errors' => ['password' => 'Password must be at least 6 characters.']];
        }

        if ($this->userRepository->findByEmail($data['email'])) {
            return ['errors' => ['email' => 'Email already exists.']];
        }

        $userId = $this->userRepository->create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => $data['phone'] ?? null,
            'age' => !empty($data['age']) ? (int)$data['age'] : null,
            'gender' => $data['gender'] ?? null,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        $_SESSION['user_id'] = $userId;

        return [
            'user' => $this->userRepository->findById($userId)
        ];
    }

    public function login(array $data): array
    {
        $errors = validate_required($data, ['email', 'password']);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return ['errors' => ['auth' => 'Invalid email or password.']];
        }

        $_SESSION['user_id'] = (int)$user['user_id'];

        return [
            'user' => $this->userRepository->findById((int)$user['user_id'])
        ];
    }

    public function updateCurrentUser(int $userId, array $data): array
    {
        $errors = validate_required($data, ['name', 'email']);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['errors' => ['email' => 'Invalid email format.']];
        }

        if ($this->userRepository->findByEmailExcludingUser(trim($data['email']), $userId)) {
            return ['errors' => ['email' => 'Email already exists.']];
        }

        $existingUser = $this->userRepository->findById($userId);
        if (!$existingUser) {
            return ['errors' => ['user' => 'User not found.']];
        }

        $password = null;
        $fullUserRow = $this->userRepository->findByEmail($existingUser['email']);
        $password = $fullUserRow['password'] ?? null;

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return ['errors' => ['password' => 'Password must be at least 6 characters.']];
            }
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $this->userRepository->update($userId, [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => $data['phone'] ?? null,
            'age' => !empty($data['age']) ? (int)$data['age'] : null,
            'gender' => $data['gender'] ?? null,
            'password' => $password
        ]);

        return [
            'user' => $this->userRepository->findById($userId)
        ];
    }

    public function deleteCurrentUser(int $userId): bool
    {
        $deleted = $this->userRepository->delete($userId);

        if ($deleted) {
            $this->logout();
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
    }
}