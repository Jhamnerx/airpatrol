<?php

use Tobuli\Entities\Device;

class DeviceRouteConfigTest extends TestCase
{
    public function testDefaultsToTripsWhenUnset()
    {
        $config = (new Device())->getRouteConfig();

        $this->assertEquals('trips', $config['type']);
        $this->assertNull($config['color']);
        $this->assertNull($config['ranges']);
        $this->assertNull($config['schedule']);
    }

    public function testInvalidTypeFallsBackToTrips()
    {
        $device = new Device();
        $device->route_color_type = 'banana';

        $this->assertEquals('trips', $device->getRouteConfig()['type']);
    }

    public function testCorruptJsonDecodesToNull()
    {
        $device = new Device([
            'route_color_type'   => 'speed',
            'route_speed_ranges' => '{not valid json',
            'route_schedule'     => '[broken',
        ]);

        $config = $device->getRouteConfig();

        $this->assertEquals('speed', $config['type']);
        $this->assertNull($config['ranges']);
        $this->assertNull($config['schedule']);
    }

    public function testValidConfigPassesThrough()
    {
        $device = new Device([
            'route_color_type'   => 'single',
            'route_color'        => '#2563eb',
            'route_speed_ranges' => '[{"from":0,"to":40,"color":"#22d3ee"},{"from":40,"to":null,"color":"#ef4444"}]',
            'route_schedule'     => '{"day_from":"06:00","day_to":"18:00","day_color":"#2563eb","night_color":"#000000"}',
        ]);

        $config = $device->getRouteConfig();

        $this->assertEquals('single', $config['type']);
        $this->assertEquals('#2563eb', $config['color']);
        $this->assertCount(2, $config['ranges']);
        $this->assertEquals(40, $config['ranges'][1]->from);
        $this->assertEquals('06:00', $config['schedule']->day_from);
    }

    public function testWrongShapeJsonDecodesToNull()
    {
        $device = new Device([
            'route_speed_ranges' => '5',
            'route_schedule'     => '"hello"',
        ]);

        $config = $device->getRouteConfig();

        $this->assertNull($config['ranges']);
        $this->assertNull($config['schedule']);
    }
}
