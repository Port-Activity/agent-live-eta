<?php

namespace SMA\PAA\TOOL;

use PHPUnit\Framework\TestCase;

final class DateToolsTest extends TestCase
{
    public function testConstructor(): void
    {
        $tools = new DateTools();
        $this->assertEquals(2, $tools->differenceSeconds("2020-06-25T12:13:14Z", "2020-06-25T12:13:16Z"));
    }
}
