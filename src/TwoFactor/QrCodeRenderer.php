<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final readonly class QrCodeRenderer
{
    private const int SIZE = 192;

    /**
     * Render the text as an SVG QR code that can be inlined in HTML.
     */
    public function svg(string $text): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(self::SIZE, 0), new SvgImageBackEnd())))
            ->writeString($text);

        // Drop the XML declaration.
        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}
