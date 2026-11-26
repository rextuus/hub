<?php

declare(strict_types=1);

namespace App\Tests\Media;

use App\Media\Imgproxy\ImgproxyUrlSigner;
use App\Media\MediaStorage;
use App\Media\MediaUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MediaUrlGeneratorTest extends TestCase
{
    private const string KEY = '943b421c9eb07c830af81030552c86009268de4e532ba2ee2eab8247c6da0881';
    private const string SALT = '520f986b998545b4785e0defbc4f3c1203f22de2374a3d53cb7a7fe9fea309c5';

    private static function generator(bool $imgproxy): MediaUrlGenerator
    {
        $router = new class implements UrlGeneratorInterface {
            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
                return '/media/'.$parameters['path'];
            }

            public function setContext(\Symfony\Component\Routing\RequestContext $context): void
            {
            }

            public function getContext(): \Symfony\Component\Routing\RequestContext
            {
                return new \Symfony\Component\Routing\RequestContext();
            }
        };

        return new MediaUrlGenerator(new ImgproxyUrlSigner($imgproxy ? self::KEY : '', $imgproxy ? self::SALT : ''), $router);
    }

    public function testImgproxyUrlForLocalFiles(): void
    {
        $url = self::generator(true)->url('/media/bet_ai/logos/a/arsenal.png', 'logo');

        self::assertStringStartsWith('/img/', $url);
        self::assertStringContainsString('/rs:fill:96:96/g:sm/q:82/', $url);
        self::assertStringEndsWith('.'.'webp', $url);
        // Quelle ist local:///bet_ai/logos/a/arsenal.png
        self::assertStringContainsString(rtrim(strtr(base64_encode('local:///bet_ai/logos/a/arsenal.png'), '+/', '-_'), '='), $url);
    }

    public function testSrcsetHasAllWidths(): void
    {
        $srcset = self::generator(true)->srcset('fotokalender/2022/mai/titel.jpg', 'photo');

        self::assertSame(3, substr_count($srcset, '/img/'));
        self::assertStringContainsString(' 960w', $srcset);
        self::assertStringContainsString(' 2400w', $srcset);
    }

    public function testWithoutImgproxyTheOriginalIsServed(): void
    {
        $generator = self::generator(false);

        self::assertSame('/media/advent/x.jpg', $generator->url('advent/x.jpg', 'advent'));
        self::assertSame('', $generator->srcset('advent/x.jpg', 'advent'));
    }

    public function testExternalUrlsPassThrough(): void
    {
        self::assertSame('https://example.com/a.png', self::generator(true)->url('https://example.com/a.png', 'logo'));
        self::assertSame('', self::generator(true)->url(null, 'logo'));
    }

    #[DataProvider('paths')]
    public function testNormalize(?string $path, ?string $expected): void
    {
        self::assertSame($expected, MediaStorage::normalize($path));
    }

    public static function paths(): iterable
    {
        yield ['fotokalender/2022/mai/titel.jpg', 'fotokalender/2022/mai/titel.jpg'];
        yield ['/media/bet_ai/logos/a.png', 'bet_ai/logos/a.png'];
        yield ['/advent/x.jpg', 'advent/x.jpg'];
        yield ['../etc/passwd', null];
        yield ['advent/../../.env', null];
        yield ['advent//x.jpg', null];
        yield ['advent\\x.jpg', null];
        yield ['https://example.com/a.png', null];
        yield ['//example.com/a.png', null];
        yield ['', null];
        yield [null, null];
    }
}
