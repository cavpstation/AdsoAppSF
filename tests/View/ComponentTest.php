<?php

namespace Illuminate\Tests\View;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    protected $viewFactory;
    protected $config;

    public function setUp(): void
    {
        $this->config = m::mock(Config::class);

        $container = new Container;

        $this->viewFactory = m::mock(Factory::class);

        $container->instance('view', $this->viewFactory);
        $container->instance('config', $this->config);

        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
    }

    public function testInlineViewsGetCreated()
    {
        $this->config->shouldReceive('get')->once()->with('view.compiled')->andReturn('/tmp');
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(false);
        $this->viewFactory->shouldReceive('addNamespace')->once()->with('__components', '/tmp');

        $component = $this->createComponent(TestInlineViewComponent::class);
        $this->assertSame('__components::c6327913fef3fca4518bcd7df1d0ff630758e241', $component->resolveView());
    }

    public function testRegularViewsGetReturned()
    {
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(true);
        $this->viewFactory->shouldReceive('addNamespace')->never();

        $component = $this->createComponent(TestRegularViewComponent::class);

        $this->assertSame('alert', $component->resolveView());
    }

    protected function createComponent($class)
    {
        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );
        });

        $component = new $class();
        $component->setContainer($container);

        return $component;
    }

    /**
     * Create a new validation factory.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return \Illuminate\Validation\Factory
     */
    protected function createValidationFactory($container)
    {
        $translator = m::mock(Translator::class)->shouldReceive('get')
            ->zeroOrMoreTimes()->andReturn('error')->getMock();

        return new ValidationFactory($translator, $container);
    }
}

class TestInlineViewComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function render()
    {
        return 'Hello {{ $title }}';
    }
}

class TestRegularViewComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function render()
    {
        return 'alert';
    }
}
