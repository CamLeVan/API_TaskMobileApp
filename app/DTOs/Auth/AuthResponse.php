<?php

namespace App\DTOs\Auth;

class AuthResponse
{
    public function __construct(
        public string $status,
        public ?array $data,
        public string $message,
        public ?string $error = null
    ) {}

    public static function success($data, string $message): self
    {
        return new self('success', $data, $message);
    }

    public static function error(string $message, ?string $error = null): self
    {
        return new self('error', null, $message, $error);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'data' => $this->data,
            'message' => $this->message,
            'error' => $this->error
        ];
    }
}