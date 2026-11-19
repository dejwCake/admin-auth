<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Lang;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every string of the two-factor settings page exists in each shipped language.
 */
class TwoFactorTranslationsTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    private const string RAW_KEY = 'brackets/admin-auth::';

    /**
     * The same key inside the JSON props, where json_encode escapes the slash.
     */
    private const string RAW_JSON_KEY = 'brackets\/admin-auth::';

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLocales(): iterable
    {
        yield 'en' => ['en'];
        yield 'sk' => ['sk'];
    }

    #[DataProvider('provideLocales')]
    public function testSettingsPageHasNoRawKeys(string $locale): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $user->forceFill(['language' => $locale])->save();
        $this->addPasskey($user);
        $this->addPasskey($user, 'YubiKey');

        $this->actingAsVerified($user)
            ->get('/admin/two-factor')
            ->assertOk()
            ->assertDontSee(self::RAW_KEY, false)
            ->assertDontSee(self::RAW_JSON_KEY, false);
    }

    #[DataProvider('provideLocales')]
    public function testEveryEnglishTwoFactorKeyIsTranslated(string $locale): void
    {
        $keys = array_keys(Arr::dot((array) trans('brackets/admin-auth::admin.two_factor', [], 'en')));

        foreach ($keys as $key) {
            $line = sprintf('brackets/admin-auth::admin.two_factor.%s', $key);
            self::assertNotSame($line, trans($line, [], $locale), $line);
        }
    }

    public function testPasskeysHintIsGone(): void
    {
        $line = 'brackets/admin-auth::admin.two_factor.passkeys_hint';

        self::assertSame($line, trans($line, [], 'en'));
        self::assertSame($line, trans($line, [], 'sk'));
    }
}
