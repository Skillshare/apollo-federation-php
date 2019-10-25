<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Utils\SchemaPrinter;

use Apollo\Federation\Directives;

class DirectivesTest extends TestCase
{
    use MatchesSnapshots;

    public function testKeyDirective()
    {
        $config = Directives::key()->config;

        $expectedLocations = [DirectiveLocation::OBJECT, DirectiveLocation::IFACE];

        $this->assertEquals($config['name'], 'key');
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

    public function testItAddsDirectivesToSchema()
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    '_' => ['type' => Type::string()]
                ]
            ]),
            'directives' => Directives::getDirectives()
        ]);

        $schemaSdl = SchemaPrinter::doPrint($schema);

        $this->assertMatchesSnapshot($schemaSdl);
    }
}
