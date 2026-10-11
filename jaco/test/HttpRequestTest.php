<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\http;

/**
 * @testdox HTTP request headers
 */
final class HttpRequestTest extends TestCase
{
    public function testHeadersAreParsedFromServerVariables(): void
    {
        $backup = $_SERVER;

        try {
            $_SERVER = [
                'HTTP_X_CUSTOM_HEADER' => 'custom-value',
                'HTTP_AUTHORIZATION' => 'Bearer abc',
                'CONTENT_TYPE' => 'application/json',
                'CONTENT_LENGTH' => '42',
                'REQUEST_METHOD' => 'GET',
            ];

            $this->assertSame(
                [
                    'X-Custom-Header' => 'custom-value',
                    'Authorization' => 'Bearer abc',
                    'Content-Type' => 'application/json',
                    'Content-Length' => '42',
                ],
                http\request_headers(),
            );
        } finally {
            $_SERVER = $backup;
        }
    }
}
