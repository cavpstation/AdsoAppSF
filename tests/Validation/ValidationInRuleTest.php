<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Tests\Validation\fixtures\Values;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ValidationInRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new In(['Laravel', 'Framework', 'PHP']);

        $this->assertSame('in:"Laravel","Framework","PHP"', (string) $rule);

        $rule = new In(['Life, the Universe and Everything', 'this is a "quote"']);

        $this->assertSame('in:"Life, the Universe and Everything","this is a ""quote"""', (string) $rule);

        $rule = new In(["a,b\nc,d"]);

        $this->assertSame("in:\"a,b\nc,d\"", (string) $rule);

        $rule = Rule::in([1, 2, 3, 4]);

        $this->assertSame('in:"1","2","3","4"', (string) $rule);

        $rule = Rule::in(collect([1, 2, 3, 4]));

        $this->assertSame('in:"1","2","3","4"', (string) $rule);

        $rule = Rule::in(new Values);

        $this->assertSame('in:"1","2","3","4"', (string) $rule);

        $rule = Rule::in('1', '2', '3', '4');

        $this->assertSame('in:"1","2","3","4"', (string) $rule);

        $rule = Rule::in(StringStatus::pending);

        $this->assertSame('in:"'.StringStatus::pending->value.'"', (string) $rule);

        $rule = Rule::in(StringStatus::pending, 'Laravel');

        $this->assertSame('in:"'.StringStatus::pending->value.'","Laravel"', (string) $rule);

        $rule = Rule::in([StringStatus::pending, StringStatus::done, 'Laravel']);

        $this->assertSame('in:"'.StringStatus::pending->value.'","'.StringStatus::done->value.'","Laravel"', (string) $rule);
    }
}
