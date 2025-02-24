<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Brokers;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker as ActivationBrokerContract;
use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Repositories\TokenRepositoryInterface;
use Closure;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use UnexpectedValueException;

class ActivationBroker implements ActivationBrokerContract
{
    public function __construct(protected TokenRepositoryInterface $tokens, protected UserProvider $users)
    {
    }

    /**
     * Send a activation link to a user.
     *
     * @param array<string, string> $credentials
     */
    public function sendActivationLink(array $credentials): string
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->getUser($credentials);

        if ($user === null) {
            return static::INVALID_USER;
        }

        // Once we have the activation token, we are ready to send the message out to this
        // user with a link to activate their account. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        $user->sendActivationNotification(
            $this->tokens->createOrGet($user),
        );

        return static::ACTIVATION_LINK_SENT;
    }

    /**
     * Activate account for the given token.
     *
     * @param array<string, string> $credentials
     */
    public function activate(array $credentials, Closure $callback): string
    {
        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
        $user = $this->validateActivation($credentials);

        if (!$user instanceof CanActivateContract) {
            return $user;
        }

        // Once the token has been validated, we'll call the given callback.
        // This gives the user an opportunity to change flag
        // in their persistent storage. Then we'll flag the token as used and return.
        $callback($user);

        $this->tokens->markAsUsed($user, $credentials['token']);

        return static::ACTIVATED;
    }

    /**
     * Get the user for the given credentials.
     *
     * @param array<string, string> $credentials
     */
    public function getUser(array $credentials): ?CanActivateContract
    {
        $credentials = Arr::except($credentials, ['token']);

        $user = $this->users->retrieveByCredentials($credentials);

        if ($user !== null && !$user instanceof CanActivateContract) {
            throw new UnexpectedValueException('User must implement CanActivateContract interface.');
        }

        return $user;
    }

    /**
     * Create a new password reset token for the given user.
     */
    public function createToken(CanActivateContract $user): string
    {
        return $this->tokens->create($user);
    }

    /**
     * Delete password reset tokens of the given user.
     */
    public function deleteToken(CanActivateContract $user): void
    {
        $this->tokens->delete($user);
    }

    /**
     * Validate the given password reset token.
     */
    public function tokenExists(CanActivateContract $user, string $token): bool
    {
        return $this->tokens->exists($user, $token);
    }

    /**
     * Get the activation token repository implementation.
     */
    public function getRepository(): TokenRepositoryInterface
    {
        return $this->tokens;
    }

    /**
     * Get the user model class implementation.
     */
    public function getUserModelClass(): ?string
    {
        return method_exists($this->users, 'getModel') ? $this->users->getModel() : null;
    }

    /**
     * Validate an activation for the given credentials.
     *
     * @param array<string, string> $credentials
     */
    protected function validateActivation(array $credentials): string|CanActivateContract
    {
        $tokenRecord = $this->tokens->getByToken($credentials['token']);
        if ($tokenRecord === null) {
            return static::INVALID_TOKEN;
        }

        $user = $this->getUser(['email' => $tokenRecord['email']]);
        if ($user === null) {
            return static::INVALID_USER;
        }

        return $user;
    }
}
