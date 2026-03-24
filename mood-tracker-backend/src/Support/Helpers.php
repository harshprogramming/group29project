<?php

function validate_required(array $data, array $fields): array
{
    $errors = [];

    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $errors[$field] = ucfirst($field) . ' is required.';
        }
    }

    return $errors;
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function sanitize_int_array($values): array
{
    if (!is_array($values)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map('intval', $values), fn($v) => $v > 0)));
}