<?php

namespace Illuminate\Tests\Pagination;

use Carbon\Carbon;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\TestCase;

class CursorTest extends TestCase
{
    public function testCanEncodeAndDecodeSuccessfully()
    {
        $cursor = new Cursor([
            'id' => 422,
            'created_at' => Carbon::now()->toDateTimeString(),
        ], true);

        $this->assertEquals($cursor, Cursor::fromEncoded($cursor->encode()));
    }

    public function testCanGetParams()
    {
        $cursor = new Cursor([
            'id' => 422,
            'created_at' => ($now = Carbon::now()->toDateTimeString()),
        ], true);

        $this->assertEquals([$now, 422], $cursor->getParams(['created_at', 'id']));
    }

    public function testCanGetParam()
    {
        $cursor = new Cursor([
            'id' => 422,
            'created_at' => ($now = Carbon::now()->toDateTimeString()),
        ], true);

        $this->assertEquals($now, $cursor->getParam('created_at'));
    }
}
