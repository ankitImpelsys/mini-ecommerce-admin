<?php

namespace App\Tests\DataProvider;

class RegisterDataProvider
{
    public static function invalidPasswords(): array
    {
        return [
            'too short' => [
                'short@example.com',
                'a@1',
                'a@1',
                'Password must be at least 6 characters.'
            ],
            'missing special char' => [
                'nospecial@example.com',
                'Password123',
                'Password123',
                'Password must contain at least one special character.'
            ],
            'passwords do not match' => [
                'mismatch@example.com',
                'Valid@123',
                'Invalid@123',
                'Passwords do not match.'
            ],
            'missing uppercase' => [
                'weak@example.com',
                'weakpass',
                'weakpass',
                'Password must contain at least one uppercase letter.'
            ],
            'missing number' => [
                'nonumber@example.com',
                'WeakPass@',
                'WeakPass@',
                'Password must contain at least one number.'
            ],
            'missing lowercase' => [
                'nolower@example.com',
                'WEAKPASS@123',
                'WEAKPASS@123',
                'Password must contain at least one lowercase letter.'
            ],
        ];
    }
}
