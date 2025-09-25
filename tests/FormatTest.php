<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use chriskacerguis\RestServer\Format;

final class FormatTest extends TestCase
{
    public function testToJson(): void
    {
        $out = Format::factory(['a' => 1, 'b' => 2])->to_json();
        $this->assertJson($out);
        $this->assertSame('{"a":1,"b":2}', $out);
    }

    public function testToArray(): void
    {
        $out = Format::factory(['a' => 1, 'b' => ['c' => 3]])->to_array();
        $this->assertIsArray($out);
        $this->assertSame(3, $out['b']['c']);
    }

    public function testToXml(): void
    {
        $xml = Format::factory(['a' => 1, 'b' => 2])->to_xml();
        $this->assertStringContainsString('<a>1</a>', $xml);
        $this->assertStringContainsString('<b>2</b>', $xml);
    }

    public function testFromSerializeDisallowsObjects(): void
    {
        $ser = serialize((object)['x' => 1]);
        $fmt = new Format($ser, 'serialize');
        $arr = $fmt->to_array();
        $this->assertIsArray($arr);
    }
}
