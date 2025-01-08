<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Repositories;

use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class DatabaseTokenRepository implements TokenRepositoryInterface
{
    protected int $expires;

    public function __construct(
        protected ConnectionInterface $connection,
        protected HasherContract $hasher,
        protected string $table,
        protected string $hashKey,
        int $expires = 60,
    ) {
        $this->expires = $expires * 60;
    }

    /**
     * Get a token record by user if exists and is valid.
     */
    public function getByUser(CanActivateContract $user): ?array
    {
        return (array) $this->getTable()
            ->where(['email' => $user->getEmailForActivation(), 'used' => false])
            ->where('created_at', '>=', CarbonImmutable::now()->subSeconds($this->expires))
            ->first();
    }

    /**
     * Get a token record by token if exists and is valid.
     */
    public function getByToken(string $token): ?array
    {
        return (array) $this->getTable()
            ->where(['token' => $token, 'used' => false])
            ->where('created_at', '>=', CarbonImmutable::now()->subSeconds($this->expires))
            ->first();
    }

    /**
     * Create a new token record.
     *
     * @throws Exception
     */
    public function create(CanActivateContract $user): string
    {
        $email = $user->getEmailForActivation();

        // We will create a new, random token for the user so that we can e-mail them
        // a safe link to activate. Then we will insert a record in
        // the database so that we can verify the token within the actual activation.
        $token = $this->createNewToken();

        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    /**
     * Create a new token or get existing not expired and not used.
     *
     * @throws Exception
     */
    public function createOrGet(CanActivateContract $user): string
    {
        $record = $this->getByUser($user);
        if ($record !== []) {
            return $record['token'];
        }

        return $this->create($user);
    }

    /**
     * Mark all token records as used by user.
     */
    public function markAsUsed(CanActivateContract $user, ?string $token = null): void
    {
        $query = $this->getTable()
            ->where('email', $user->getEmailForActivation());
        if ($token !== null) {
            $query = $query->where('token', $token);
        }
        $query->update(['used' => true]);
    }

    /**
     * Determine if a token record exists and is valid.
     */
    public function exists(CanActivateContract $user, string $token): bool
    {
        $record = (array) $this->getTable()->where(
            ['email' => $user->getEmailForActivation(), 'used' => false],
        )->first();

        return $record !== []
            && isset($record['created_at'], $record['token'])
            && !$this->tokenExpired($record['created_at'])
            && $token === $record['token'];
    }

    /**
     * Delete a token record by user.
     */
    public function delete(CanActivateContract $user): void
    {
        $this->deleteExisting($user);
    }

    /**
     * Delete expired tokens.
     */
    public function deleteExpired(): void
    {
        $expiredAt = CarbonImmutable::now()->subSeconds($this->expires);

        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }

    /**
     * Create a new token for the user.
     */
    public function createNewToken(): string
    {
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    /**
     * Get the database connection instance.
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Get the hasher instance.
     */
    public function getHasher(): HasherContract
    {
        return $this->hasher;
    }

    /**
     * Delete all existing activation tokens from the database.
     */
    protected function deleteExisting(CanActivateContract $user): ?int
    {
        return $this->getTable()->where('email', $user->getEmailForActivation())->delete();
    }

    /**
     * Build the record payload for the table.
     *
     * @throws Exception
     * @return array<string, string|CarbonInterface>
     */
    protected function getPayload(string $email, string $token): array
    {
        return ['email' => $email, 'token' => $token, 'created_at' => CarbonImmutable::now()];
    }

    /**
     * Determine if the token has expired.
     */
    protected function tokenExpired(string $createdAt): bool
    {
        return CarbonImmutable::parse($createdAt)->addSeconds($this->expires)->isPast();
    }

    /**
     * Begin a new database query against the table.
     */
    protected function getTable(): Builder
    {
        return $this->connection->table($this->table);
    }
}
