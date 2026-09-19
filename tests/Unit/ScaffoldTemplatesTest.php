<?php

namespace ZeroOneZ\Dashboard\Tests\Unit;

use ZeroOneZ\Dashboard\Services\Dashboard\ScaffoldTemplatesV2;
use ZeroOneZ\Dashboard\Services\Dashboard\BuilderHandoff;
use ZeroOneZ\Dashboard\Tests\TestCase;

final class ScaffoldTemplatesTest extends TestCase
{
    public function test_generated_controller_exposes_customizable_dashboard_hooks(): void
    {
        $definition = [
            'resource' => 'cities',
            'permission' => 'city',
            'controller' => 'CityController',
            'model' => 'City',
            'create_model' => false,
            'timestamps' => true,
            'capabilities' => [
                'create' => true, 'update' => true, 'delete' => true,
                'delete_all' => false, 'export' => false, 'filters' => true, 'show' => false,
            ],
            'fields' => [],
            'edit_mode' => 'same',
            'edit_fields' => [],
        ];

        $files = app(ScaffoldTemplatesV2::class)->files($definition);
        $controller = $files['app/Http/Controllers/Admin/Generated/CityController.php'];

        $this->assertStringContainsString('public function inputs_list(): array', $controller);
        $this->assertStringContainsString('public function show_list(): array', $controller);
        $this->assertStringContainsString('public function store(Request $request)', $controller);
        $this->assertStringContainsString('public function update(Request $request, $id)', $controller);
        $this->assertStringContainsString('public function get_single_item($id)', $controller);
    }

    public function test_route_block_is_compact_and_uses_configured_controller_namespace(): void
    {
        config()->set('dashboard.generator.controller_namespace', 'Domain\\Admin\\Generated');
        $definition = ['resource' => 'cities', 'permission' => 'city', 'controller' => 'CityController'];
        $route = app(ScaffoldTemplatesV2::class)->routeBlock($definition);
        $this->assertStringContainsString('Domain\\Admin\\Generated\\CityController::class', $route);
        $this->assertStringContainsString("all_routes('city');", $route);
        $this->assertStringNotContainsString("Route::post('/store'", $route);
    }

    public function test_handoff_uses_configured_paths_namespaces_and_route_prefixes(): void
    {
        config()->set('dashboard.generator.controller_path', 'src/Admin/Generated');
        config()->set('dashboard.generator.controller_namespace', 'Domain\\Admin\\Generated');
        config()->set('dashboard.generator.definition_path', 'var/dashboard/resources');
        config()->set('dashboard.generator.model_path', 'src/Models');
        config()->set('dashboard.generator.model_namespace', 'Domain\\Models');
        config()->set('dashboard.generator.routes_path', 'routes/backoffice-generated');
        config()->set('dashboard.generator.translations_path', 'lang');
        config()->set('dashboard.route_prefix', 'backoffice');
        config()->set('dashboard.route_name_prefix', 'backoffice.');

        $definition = [
            'resource' => 'cities', 'controller' => 'CityController', 'model' => 'City',
            'permission' => 'city', 'create_model' => false, 'fields' => [],
            'edit_mode' => 'same', 'edit_fields' => [], 'actions' => [], 'modals' => [],
            'notes' => '', 'capabilities' => ['show' => false],
        ];
        $handoff = app(BuilderHandoff::class)->package($definition, ['custom implementation']);

        $paths = array_column($handoff['file_plan'], 'path');
        $this->assertContains('src/Admin/Generated/CityController.php', $paths);
        $this->assertContains('routes/backoffice-generated/cities.php', $paths);
        $this->assertStringContainsString('`backoffice.cities.index`', $handoff['markdown']);
        $this->assertStringContainsString('`/backoffice/cities`', $handoff['markdown']);
        $this->assertStringContainsString('Domain\\Models\\City', $handoff['markdown']);
    }
}
