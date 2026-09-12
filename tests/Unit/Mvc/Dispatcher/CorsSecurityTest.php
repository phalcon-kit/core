<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Dispatcher;

use Phalcon\Http\Response;
use PhalconKit\Mvc\Dispatcher\Preflight;
use PHPUnit\Framework\TestCase;

/** Browser credential and shared-cache boundaries for framework CORS headers. */
final class CorsSecurityTest extends TestCase
{
    public function testDefaultCorsMustNotGrantCredentialsToAnUntrustedOrigin(): void
    {
        $response = new Response();
        (new Preflight())->setCorsHeaders($response, 'https://untrusted.example.test', (new \PhalconKit\Bootstrap\Config())->pathToArray('response.corsHeaders'));
        $headers = $response->getHeaders();
        self::assertFalse(
            $headers->get('Access-Control-Allow-Origin') === 'https://untrusted.example.test'
                && $headers->get('Access-Control-Allow-Credentials') === 'true',
            'Default CORS reflects arbitrary origins and allows browser credentials.'
        );
    }

    public function testExplicitCorsAllowlistRejectsOtherOriginsControl(): void
    {
        $response = new Response();
        $headers = (new \PhalconKit\Bootstrap\Config())->pathToArray('response.corsHeaders');
        $headers['Access-Control-Allow-Origin'] = ['https://trusted.example.test'];
        (new Preflight())->setCorsHeaders($response, 'https://untrusted.example.test', $headers);
        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testExplicitCorsOriginAllowsCredentialsAndVariesCache(): void
    {
        $response = new Response();
        $response->setHeader('Vary', 'Accept-Encoding');
        (new Preflight())->setCorsHeaders($response, 'https://trusted.example.test', [
            'Access-Control-Allow-Origin' => ['https://trusted.example.test'],
            'Access-Control-Allow-Credentials' => true,
        ]);
        self::assertSame('https://trusted.example.test', $response->getHeaders()->get('Access-Control-Allow-Origin'));
        self::assertSame('true', $response->getHeaders()->get('Access-Control-Allow-Credentials'));
        self::assertSame('Accept-Encoding, Origin', $response->getHeaders()->get('Vary'));
    }

    public function testWildcardCorsCannotReflectCredentialedOrigins(): void
    {
        $response = new Response();
        (new Preflight())->setCorsHeaders($response, 'https://untrusted.example.test', [
            'Access-Control-Allow-Origin' => ['*'],
            'Access-Control-Allow-Credentials' => true,
        ]);
        self::assertSame('*', $response->getHeaders()->get('Access-Control-Allow-Origin'));
        self::assertSame('false', $response->getHeaders()->get('Access-Control-Allow-Credentials'));
    }
}
