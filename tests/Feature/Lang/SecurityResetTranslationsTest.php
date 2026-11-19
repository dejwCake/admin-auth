<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Lang;

use Brackets\AdminAuth\Notifications\SecurityResetNotification;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\AnonymousNotifiable;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every string of the admin security reset exists in each shipped language.
 */
class SecurityResetTranslationsTest extends AdminUserTestCase
{
    private const string RAW_KEY = 'brackets/admin-auth::';

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLocales(): iterable
    {
        yield 'en' => ['en'];
        yield 'sk' => ['sk'];
    }

    #[DataProvider('provideLocales')]
    public function testNotificationHasNoRawKeys(string $locale): void
    {
        $this->app->setLocale($locale);

        $mail = (new SecurityResetNotification('John Doe', CarbonImmutable::parse('2026-11-17 10:00:00')))
            ->toMail(new AnonymousNotifiable());
        $text = implode(' ', [$mail->subject, ...$mail->introLines]);

        self::assertStringNotContainsString(self::RAW_KEY, $text);
        self::assertStringContainsString('John Doe', $text);
    }

    #[DataProvider('provideLocales')]
    public function testEveryEnglishKeyIsTranslated(string $locale): void
    {
        $keys = $this->keys((array) trans('brackets/admin-auth::admin.security_reset', [], 'en'));

        foreach ($keys as $key) {
            $line = sprintf('brackets/admin-auth::admin.security_reset.%s', $key);
            self::assertNotSame($line, trans($line, [], $locale), $line);
        }
    }

    /**
     * @param array<string, string|array<string, string>> $lines
     * @return array<int, string>
     */
    private function keys(array $lines, string $prefix = ''): array
    {
        $keys = [];
        foreach ($lines as $key => $line) {
            $name = $prefix === '' ? $key : sprintf('%s.%s', $prefix, $key);
            $keys = is_array($line) ? [...$keys, ...$this->keys($line, $name)] : [...$keys, $name];
        }

        return $keys;
    }
}
