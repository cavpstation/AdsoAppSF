<?php

namespace Illuminate\Tests\View\Blade;

class BladeUnsetStatementsTest extends AbstractBladeTestCase
{
    public function testUnsetStatementsAreCompiled(): void
    {
        $string = '@unset ($unset)';
        $expected = '<?php unset($unset); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
