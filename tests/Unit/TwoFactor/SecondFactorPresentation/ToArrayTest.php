<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\TwoFactor\SecondFactorPresentation;

use Brackets\AdminAuth\TwoFactor\SecondFactorLink;
use Brackets\AdminAuth\TwoFactor\SecondFactorPresentation;
use PHPUnit\Framework\TestCase;

class ToArrayTest extends TestCase
{
    public function testComponentFactor(): void
    {
        $presentation = new SecondFactorPresentation(
            key: 'totp',
            name: 'Authenticator app',
            icon: 'fa fa-mobile-screen',
            available: true,
            active: true,
            status: 'On',
            component: 'two-factor-settings',
            props: ['enabled' => true, 'enable-url' => '/admin/two-factor/enable'],
        );

        self::assertSame([
            'key' => 'totp',
            'name' => 'Authenticator app',
            'icon' => 'fa fa-mobile-screen',
            'available' => true,
            'active' => true,
            'status' => 'On',
            'notice' => null,
            'url' => null,
            'link_label' => null,
            'component' => 'two-factor-settings',
            'props' => ['enabled' => true, 'enable-url' => '/admin/two-factor/enable'],
        ], $presentation->toArray());
    }

    public function testLinkFactor(): void
    {
        $presentation = new SecondFactorPresentation(
            key: 'passkey',
            name: 'Passkeys',
            icon: 'fa fa-fingerprint',
            available: true,
            active: false,
            status: 'None',
            link: new SecondFactorLink('/admin/passkeys', 'Manage passkeys'),
        );

        $array = $presentation->toArray();

        self::assertSame('/admin/passkeys', $array['url']);
        self::assertSame('Manage passkeys', $array['link_label']);
        self::assertNull($array['component']);
        self::assertSame([], $array['props']);
    }

    public function testUnavailableFactor(): void
    {
        $presentation = new SecondFactorPresentation(
            key: 'passkey',
            name: 'Passkeys',
            icon: 'fa fa-fingerprint',
            available: false,
            active: false,
            status: 'None',
            notice: 'Run the migration.',
        );

        $array = $presentation->toArray();

        self::assertFalse($array['available']);
        self::assertSame('Run the migration.', $array['notice']);
    }
}
