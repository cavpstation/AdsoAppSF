<?php

namespace Illuminate\Tests\View\Blade;

class BladePushTest extends AbstractBladeTestCase
{
    public function testPushIsCompiled(): void
    {
        $string = '@push(\'foo\')
test
@endpush';
        $expected = '<?php $__env->startPush(\'foo\'); ?>
test
<?php $__env->stopPush(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
