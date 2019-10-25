<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use PHPUnit\Framework\TestCase;

use GraphQL\Language\DirectiveLocation;
use Apollo\Federation\Directives;

class SchemaTest extends TestCase
{
    public function testKeyDirective()
    {
        $config = Directives::key()->config;

        $expectedLocations = [DirectiveLocation::OBJECT, DirectiveLocation::IFACE];

        $this->assertEquals($config['name'], 'key');
        $this->assertEqualsCanonicalizing($config['locations'], $expectedLocations);
    }

    public function testExtendsDirective()
    {
        $config = Directives::extends()->config;

        $expectedLocations = [DirectiveLocation::OBJECT];

        $this->assertEquals($config['name'], 'extends');
        $this->assertEqualsCanonicalizing($config['locations'], $expectedLocations);
    }

    public function testExternalDirective()
    {
        $config = Directives::external()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION];

        $this->assertEquals($config['name'], 'external');
        $this->assertEqualsCanonicalizing($config['locations'], $expectedLocations);
    }

    public function testRequiresDirective()
    {
        $config = Directives::requires()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION];

        $this->assertEquals($config['name'], 'requires');
        $this->assertEqualsCanonicalizing($config['locations'], $expectedLocations);
    }

    public function testProvidesDirective()
    {
        $config = Directives::provides()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION];

        $this->assertEquals($config['name'], 'provides');
        $this->assertEqualsCanonicalizing($config['locations'], $expectedLocations);
    }
}
