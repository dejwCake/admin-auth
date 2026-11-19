<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * A logger that keeps what was logged, for assertions.
 */
final class RecordingLogger extends AbstractLogger
{
    /**
     * @var array<int, array{
     *     level: string,
     *     message: string,
     *     context: array<array-key, array|string|int|float|bool|object|null>,
     * }>
     */
    private array $records = [];

    /**
     * The parent leaves $level untyped, so this override must too.
     *
     * @param string $level
     * @param array<array-key, array|string|int|float|bool|object|null> $context
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }

    /**
     * The context of the first record with this level and message, or null.
     *
     * @return array<array-key, array|string|int|float|bool|object|null>|null
     */
    public function context(string $level, string $message): ?array
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level && $record['message'] === $message) {
                return $record['context'];
            }
        }

        return null;
    }
}
