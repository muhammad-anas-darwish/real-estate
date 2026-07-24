<?php

namespace Modules\RealEstate\Tests;

use Modules\RealEstate\ValueObjects\MapBounds;
use Tests\TestCase;

class MapBoundsTest extends TestCase
{
    public function test_from_center_creates_correct_bounds(): void
    {
        $bounds = MapBounds::fromCenter(24.7136, 46.6753, 5.0);

        $this->assertLessThan(24.7136, $bounds->swLat);
        $this->assertGreaterThan(24.7136, $bounds->neLat);
        $this->assertLessThan(46.6753, $bounds->swLng);
        $this->assertGreaterThan(46.6753, $bounds->neLng);
    }

    public function test_contains_returns_true_for_point_inside(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $this->assertTrue($bounds->contains(24.5, 46.5));
    }

    public function test_contains_returns_false_for_point_outside(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $this->assertFalse($bounds->contains(26.0, 46.5));
        $this->assertFalse($bounds->contains(24.5, 45.5));
    }

    public function test_invalid_bounds_throw_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MapBounds(25.0, 46.0, 24.0, 47.0);
    }

    public function test_to_array_returns_sw_ne(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $array = $bounds->toArray();

        $this->assertEquals(24.0, $array['sw']['lat']);
        $this->assertEquals(46.0, $array['sw']['lng']);
        $this->assertEquals(25.0, $array['ne']['lat']);
        $this->assertEquals(47.0, $array['ne']['lng']);
    }
}
