<?php

declare(strict_types=1);

namespace ServerCreator\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Pterodactyl\Models\Egg;
use ReflectionClass;
use ReflectionMethod;
use ServerCreator\Panel\Panel;

final class PanelEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        $autoload = __DIR__.'/../../.panel/vendor/autoload.php';
        if (! is_file($autoload) || ! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requires the pinned Panel dependencies and pdo_sqlite.');
        }
        require_once $autoload;
        $container = new Container;
        Container::setInstance($container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        $translator = new Translator(new ArrayLoader, 'en');
        $container->instance('translator', $translator);
        $container->instance('validator', new Factory($translator, $container));
        $database = new Manager($container);
        $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $database->setAsGlobal();
        $database->bootEloquent();
        $container->instance('db', $database->getDatabaseManager());
        $container->bind('db.schema', fn () => $database->schema());
        $database->schema()->create('eggs', function ($table): void {
            $table->increments('id');
        });
        $database->table('eggs')->insert(['id' => 1]);
        $database->schema()->create('egg_variables', function ($table): void {
            $table->increments('id');
            $table->integer('egg_id');
            $table->string('name');
            $table->string('env_variable');
            $table->string('default_value');
            $table->string('rules');
            $table->boolean('user_viewable');
            $table->boolean('user_editable');
        });
        foreach ([['TELNET_PORT', '', true], ['PRIVATE_PORT', '', false], ['VERSION', 'latest', true], ['ADMIN_ONLY', 'protected', false]] as [$key, $default, $editable]) {
            $database->table('egg_variables')->insert(['egg_id' => 1, 'name' => $key, 'env_variable' => $key, 'default_value' => $default, 'rules' => str_contains($key, 'PORT') ? 'required|integer|between:1,65535' : 'required|string', 'user_viewable' => $editable, 'user_editable' => $editable]);
        }
    }

    public function test_required_ports_are_filled_before_validation_and_override_user_input(): void
    {
        $egg = new Egg;
        $egg->id = 1;
        $panel = (new ReflectionClass(Panel::class))->newInstanceWithoutConstructor();
        $environment = $panel->environment($egg, ['VERSION' => 'latest', 'TELNET_PORT' => 'invalid', 'ADMIN_ONLY' => 'injected'], ['TELNET_PORT' => '27020', 'PRIVATE_PORT' => '27021']);
        $this->assertSame('27020', $environment['TELNET_PORT']);
        $this->assertSame('27021', $environment['PRIVATE_PORT']);
        $this->assertSame('protected', $environment['ADMIN_ONLY']);
        $this->assertSame('latest', $environment['VERSION']);
    }

    public function test_missing_normal_required_variable_is_still_rejected(): void
    {
        $egg = new Egg;
        $egg->id = 1;
        $panel = (new ReflectionClass(Panel::class))->newInstanceWithoutConstructor();
        $this->expectException(ValidationException::class);
        $panel->environment($egg, [], ['TELNET_PORT' => '27020', 'PRIVATE_PORT' => '27021']);
    }

    public function test_required_port_omitted_by_user_is_populated(): void
    {
        $egg = new Egg;
        $egg->id = 1;
        $panel = (new ReflectionClass(Panel::class))->newInstanceWithoutConstructor();
        $environment = $panel->environment($egg, ['VERSION' => 'latest'], ['TELNET_PORT' => '27020', 'PRIVATE_PORT' => '27021']);
        $this->assertSame('27020', $environment['TELNET_PORT']);
        $panel->validateEnvironment($egg, $environment);
    }

    public function test_migration_preserves_legacy_mode_and_can_be_reversed(): void
    {
        $schema = app('db.schema');
        $schema->create('ext_server_creator_games', function ($table): void {
            $table->increments('id');
        });
        app('db')->table('ext_server_creator_games')->insert(['id' => 1]);
        $migration = require __DIR__.'/../../database/migrations/2026_11_09_000000_add_port_mode_to_ext_server_creator_games.php';
        $migration->up();
        $this->assertSame('consecutive', app('db')->table('ext_server_creator_games')->value('port_mode'));
        $migration->down();
        $this->assertFalse($schema->hasColumn('ext_server_creator_games', 'port_mode'));
        $this->assertSame(1, app('db')->table('ext_server_creator_games')->count());
    }

    public function test_extra_count_is_added_to_primary_and_not_persisted_as_an_unknown_column(): void
    {
        $controller = (new ReflectionClass(\ServerCreator\Http\Controllers\AdminController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, 'validatePorts');
        $data = $method->invoke($controller, ['egg_id' => 1, 'ports' => 9, 'extra_ports' => 2, 'port_mode' => 'automatic', 'port_variables' => ['TELNET_PORT']]);
        $this->assertSame(3, $data['ports']);
        $this->assertArrayNotHasKey('extra_ports', $data);
        $this->assertSame(['TELNET_PORT'], $data['port_variables']);
        $game = new \ServerCreator\Models\Game($data);
        $this->assertSame(3, $game->portCount());
    }

    public function test_extra_port_count_never_silently_increases_to_fit_variables(): void
    {
        $controller = (new ReflectionClass(\ServerCreator\Http\Controllers\AdminController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, 'validatePorts');
        $this->expectException(ValidationException::class);
        $method->invoke($controller, ['egg_id' => 1, 'ports' => 1, 'extra_ports' => 0, 'port_variables' => ['TELNET_PORT']]);
    }
}
