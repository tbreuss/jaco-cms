<?php declare(strict_types=1);

namespace jaco\tests;

use jaco\core\http\Status;
use jaco\core\router;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Maintenance mode is enforced in the router, not in the actions. Every route
 * that changes data is a POST, so the method decides; only the way back in is
 * exempt. The route table is checked as well, so a new POST route cannot add a
 * writing endpoint that skips the brake.
 */
#[TestDox('Maintenance mode (enforced in the router)')]
final class ApiMaintenanceTest extends ApiTestCase
{
    /**
     * Every POST route that is not exempt has to answer 503. The list comes from
     * the route table itself, so a new writing route is covered without touching
     * this test.
     */
    public function testEveryWritingRouteIsBlockedWhileMaintenanceModeIsOn(): void
    {
        $exempt = router\MAINTENANCE_EXCEPTIONS;
        $checked = [];

        foreach (router\routes() as $route) {
            if ($route['method'] !== 'POST' || \in_array($route['action'], $exempt, true)) {
                continue;
            }

            $checked[] = $route['action'];
            $response = $this->api($route['action'], [], 'POST', true, config: test_config_with(['maintenanceMode' => true]));

            $this->assertSame(Status::SERVICE_UNAVAILABLE, $response->status, $route['action'] . ' must be blocked');

            $body = (array) json_decode((string) $response->body, true);
            $this->assertSame('Maintenance mode: changes are disabled', $body[0]['title']);
        }

        // a route table without writing routes would make the loop pass silently
        $this->assertContains('page/update', $checked);
        $this->assertContains('css/update', $checked);
        $this->assertContains('template/site/update', $checked);
        $this->assertNotContains('login', $checked);
        $this->assertNotContains('system/change-password', $checked);
    }

    public function testReadingRouteStillAnswersWhileMaintenanceModeIsOn(): void
    {
        $response = $this->api('page/list', [], 'GET', true, config: test_config_with(['maintenanceMode' => true]));

        $this->assertSame(Status::OK, $response->status);
    }

    public function testChangePasswordWorksWhileMaintenanceModeIsOn(): void
    {
        // the way back in must not depend on the brake it is meant to recover from
        $this->assertContains('system/change-password', router\MAINTENANCE_EXCEPTIONS);
    }

    public function testLoginIsExemptSoUsersCanStillSignIn(): void
    {
        $response = $this->api('login', [], 'POST', false, config: test_config_with(['maintenanceMode' => true]));

        // no credentials in the request: the route answers, it is not blocked
        $this->assertNotSame(Status::SERVICE_UNAVAILABLE, $response->status);
    }
}
