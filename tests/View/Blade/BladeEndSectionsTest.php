<?php

namespace Illuminate\Tests\View\Blade;

class BladeEndSectionsTest extends AbstractBladeTestCase
{
    public function testEndSectionsAreCompiled(): void
    {
        $this->assertEquals('<?php $__env->stopSection(); ?>', $this->compiler->compileString('@endsection'));
    }
}
