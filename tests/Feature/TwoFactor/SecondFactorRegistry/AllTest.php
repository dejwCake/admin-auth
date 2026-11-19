<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\SecondFactorRegistry;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TestSecondFactor;
use Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor;
use Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor;
use Brackets\AdminAuth\TwoFactor\Factors\TotpFactor;
use Brackets\AdminAuth\TwoFactor\SecondFactorRegistry;
use Illuminate\Contracts\Config\Repository as Config;
use InvalidArgumentException;
use stdClass;

class AllTest extends AdminUserTestCase
{
    public function testDefaultOrder(): void
    {
        self::assertSame([TotpFactor::class, PasskeyFactor::class], $this->classes());
    }

    public function testMissingKeyFallsBackToTheDefaults(): void
    {
        $config = $this->app->make(Config::class);
        $twoFactor = $config->get('admin-auth.two_factor');
        unset($twoFactor['factors']);
        $config->set('admin-auth.two_factor', $twoFactor);

        self::assertSame(SecondFactorRegistry::DEFAULT_FACTORS, $this->classes());
    }

    public function testAdditionalFactorComesLast(): void
    {
        $this->setFactors([TotpFactor::class, PasskeyFactor::class, TestSecondFactor::class]);

        self::assertSame([TotpFactor::class, PasskeyFactor::class, TestSecondFactor::class], $this->classes());
    }

    public function testUnknownClassIsNamed(): void
    {
        $this->setFactors([TotpFactor::class, 'App\TwoFactor\MissingFactor']);

        self::assertStringContainsString('App\TwoFactor\MissingFactor', $this->failureMessage());
    }

    public function testNonFactorClassIsNamed(): void
    {
        $this->setFactors([stdClass::class]);

        self::assertStringContainsString('stdClass', $this->failureMessage());
    }

    private function failureMessage(): string
    {
        try {
            $this->app->make(SecondFactorRegistry::class)->all();
        } catch (InvalidArgumentException $exception) {
            return $exception->getMessage();
        }

        self::fail('The registry accepted an invalid factor class.');
    }

    /**
     * @param array<int, string> $factors
     */
    private function setFactors(array $factors): void
    {
        $this->app->make(Config::class)->set('admin-auth.two_factor.factors', $factors);
    }

    /**
     * @return array<int, class-string>
     */
    private function classes(): array
    {
        return array_map(
            static fn (SecondFactor $factor): string => $factor::class,
            $this->app->make(SecondFactorRegistry::class)->all(),
        );
    }
}
