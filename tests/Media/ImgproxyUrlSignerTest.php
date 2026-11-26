<?php

declare(strict_types=1);

namespace App\Tests\Media;

use App\Media\Imgproxy\ImgproxyUrlSigner;
use PHPUnit\Framework\TestCase;

final class ImgproxyUrlSignerTest extends TestCase
{
    /**
     * Schlüssel/Salt aus dem imgproxy-Beispiel; die Signatur ist unabhängig nachgerechnet (Python hmac/sha256) und
     * gegen einen echten imgproxy v4.0.25 geprüft (gültig 200, verfälscht 403).
     */
    public function testKnownSignature(): void
    {
        $signer = new ImgproxyUrlSigner(
            '943b421c9eb07c830af81030552c86009268de4e532ba2ee2eab8247c6da0881',
            '520f986b998545b4785e0defbc4f3c1203f22de2374a3d53cb7a7fe9fea309c5',
        );

        self::assertSame(
            '/4e9HplX3xN8DgbCf3GPatgfy42W8pLOfGHxXdnPxeHw/rs:fill:300:400:0/g:sm/aHR0cDovL2V4YW1wbGUuY29tL2ltYWdlcy9jdXJpb3NpdHkuanBn.png',
            $signer->sign('http://example.com/images/curiosity.jpg', ['rs:fill:300:400:0', 'g:sm'], 'png'),
        );
    }

    public function testDisabledWithoutKeys(): void
    {
        self::assertFalse(new ImgproxyUrlSigner('', '')->isEnabled());
        self::assertFalse(new ImgproxyUrlSigner(null, 'ab')->isEnabled());
        self::assertTrue(new ImgproxyUrlSigner('ab', 'cd')->isEnabled());
    }
}
