<?php

namespace Illuminate\Tests\View\Blade;

class BladeContinueStatementsTest extends AbstractBladeTestCase
{
    public function testContinueStatementsAreCompiled(): void
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testContinueStatementsWithExpressionAreCompiled(): void
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(TRUE)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php if(TRUE) continue; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testContinueStatementsWithArgumentAreCompiled(): void
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testContinueStatementsWithSpacedArgumentAreCompiled(): void
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue( 2 )
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testContinueStatementsWithFaultyArgumentAreCompiled(): void
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(-2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue 1; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
