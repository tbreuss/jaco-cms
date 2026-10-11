<?php declare(strict_types=1);

namespace jaco\tests;

use PHPUnit\Framework\TestCase;
use jaco\core\http\Status;
use jaco\core\responder;
use jaco\domain\page;

/**
 * @testdox Error responses and field mapping
 */
final class ResponderMappingTest extends TestCase
{
    public function testClientErrorsAreMappedToApiFieldNames(): void
    {
        $errors = responder\transform_client_errors(
            [['file', 'bad'], ['other', 'msg']],
            ['file' => 'fileName'],
        );

        $this->assertSame([
            ['source' => ['pointer' => 'fileName'], 'title' => 'Validation Error', 'detail' => 'bad'],
            ['source' => ['pointer' => ''], 'title' => 'Validation Error', 'detail' => 'msg'],
        ], $errors);
    }

    public function testServerErrorsUseTheirOwnTitle(): void
    {
        $errors = responder\transform_server_errors([['file', 'boom']], []);

        $this->assertSame('Server Error', $errors[0]['title']);
        $this->assertSame('', $errors[0]['source']['pointer']);
        $this->assertSame('boom', $errors[0]['detail']);
    }

    public function testInvalidPayloadBecomesA422Response(): void
    {
        $payload = page\DomainPayload::invalid([['file', 'Could not write file.']]);

        $response = responder\api_errors($payload, ['file' => 'fileName']);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('fileName', $body[0]['source']['pointer']);
        $this->assertSame('Validation Error', $body[0]['title']);
        $this->assertSame('Could not write file.', $body[0]['detail']);
    }

    public function testErrorPayloadBecomesA500Response(): void
    {
        $payload = page\DomainPayload::error([['file', 'Could not backup file.']]);

        $response = responder\api_errors($payload, []);

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('Server Error', $body[0]['title']);
    }

    public function testSuccessPayloadHasNoErrorRepresentation(): void
    {
        $response = responder\api_errors(page\DomainPayload::success(), []);

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('Invalid service status', $body[0]['detail']);
    }

    public function testMaintenanceResponseIsNullWhenDisabled(): void
    {
        $this->assertNull(responder\maintenance_response(false));
        $this->assertNull(responder\maintenance_response());
    }

    public function testMaintenanceResponseIsA503ErrorWhenEnabled(): void
    {
        $response = responder\maintenance_response(true);

        $this->assertNotNull($response);
        $this->assertSame(Status::SERVICE_UNAVAILABLE, $response->status);

        $body = (array) json_decode((string) $response->body, true);
        $this->assertSame('Maintenance mode: changes are disabled', $body[0]['title']);
        $this->assertSame('Changes are disabled while maintenance mode is enabled.', $body[0]['detail']);

        // the router validates error responses, so the body must comply
        $this->assertSame([], \jaco\core\http\validate_response($response->body, 'errors.json'));
    }
}
