<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Lang;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every string of the passkey pages exists in each shipped language.
 */
class PasskeyTranslationsTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

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
    public function testLoginPageHasNoRawKeys(string $locale): void
    {
        $this->app->setLocale($locale);

        $this->get('/admin/login')->assertOk()->assertDontSee(self::RAW_KEY, false);
    }

    #[DataProvider('provideLocales')]
    public function testPasskeysAndTwoFactorPagesHaveNoRawKeys(string $locale): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $user->forceFill(['language' => $locale])->save();
        $this->addPasskey($user);

        $this->actingAsVerified($user)->get('/admin/passkeys')->assertOk()->assertDontSee(self::RAW_KEY, false);
        $this->get('/admin/two-factor')->assertOk()->assertDontSee(self::RAW_KEY, false);
    }

    #[DataProvider('provideLocales')]
    public function testChallengePageHasNoRawKeys(string $locale): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $user->forceFill(['language' => $locale])->save();
        $this->addPasskey($user);
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->get('/admin/two-factor/challenge')
            ->assertOk()
            ->assertDontSee(self::RAW_KEY, false);
    }

    #[DataProvider('provideLocales')]
    public function testEveryEnglishPasskeyKeyIsTranslated(string $locale): void
    {
        $keys = array_keys((array) trans('brackets/admin-auth::admin.passkeys', [], 'en'));

        foreach ($keys as $key) {
            $line = sprintf('brackets/admin-auth::admin.passkeys.%s', $key);
            self::assertNotSame($line, trans($line, [], $locale), $line);
        }
    }
}
