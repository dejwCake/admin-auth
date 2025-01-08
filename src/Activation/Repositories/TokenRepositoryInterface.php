<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Repositories;

use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;

interface TokenRepositoryInterface
{
    /**
     * Get a token record by user if exists and is valid.
     */
    public function getByUser(CanActivateContract $user): ?array;

    /**
     * Get a token record by token if exists and is valid.
     */
    public function getByToken(string $token): ?array;

    /**
     * Create a new token.
     */
    public function create(CanActivateContract $user): string;

    /**
     * Create a new token or get existing not expired and not used.
     */
    public function createOrGet(CanActivateContract $user): string;

    /**
     * Mark all token records as used by user.
     */
    public function markAsUsed(CanActivateContract $user, ?string $token): void;

    /**
     * Determine if a token record exists and is valid.
     */
    public function exists(CanActivateContract $user, string $token): bool;

    /**
     * Delete a token record.
     */
    public function delete(CanActivateContract $user): void;

    /**
     * Delete expired tokens.
     */
    public function deleteExpired(): void;
}
