<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Apollo\Federation\Directives;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class DirectivesTest extends TestCase
{
    use MatchesSnapshots;

    public function testKeyDirective(): void
    {
        $config = Directives::key()->config;

        $expectedLocations = [DirectiveLocation::OBJECT, DirectiveLocation::IFACE];

        $this->assertEquals('key', $config['name']);
        $this->assertEqualsCanonicalizing($expectedLocations, $config['locations']);
    }

    public function testExternalDirective(): void
    {
        $config = Directives::external()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION];

        $this->assertEquals('external', $config['name']);
        $this->assertEqualsCanonicalizing($expectedLocations, $config['locations']);
    }

    public function testInaccessibleDirective(): void
    {
        $config = Directives::inaccessible()->config;

        $expectedLocations = [
            DirectiveLocation::FIELD_DEFINITION,
            DirectiveLocation::IFACE,
            DirectiveLocation::OBJECT,
            DirectiveLocation::UNION,
        ];

        $this->assertEquals('inaccessible', $config['name']);
        $this->assertEqualsCanonicalizing($expectedLocations, $config['locations']);
    }

    public function testRequiresDirective(): void
    {
        $config = Directives::requires()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION];

        $this->assertEquals('requires', $config['name']);
        $this->assertEqualsCanonicalizing($expectedLocations, $config['locations']);
    }

    public function testProvidesDirective(): void
    {
        $config = Directives::provides()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION];

        $this->assertEquals('provides', $config['name']);
        $this->assertEqualsCanonicalizing($expectedLocations, $config['locations']);
    }

    public function testShareableDirective(): void
    {
        $config = Directives::shareable()->config;

        $expectedLocations = [DirectiveLocation::FIELD_DEFINITION, DirectiveLocation::OBJECT];

        $this->assertEquals('shareable', $config['name']);
        $this->assertEqualsCanonicalizing($expectedLocations, $config['locations']);
    }

    public function testItAddsDirectivesToSchema(): void
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    '_' => ['type' => Type::string()],
                ],
            ]),
            'directives' => Directives::getDirectives(),
        ]);

        $schemaSdl = SchemaPrinter::doPrint($schema);

        $this->assertMatchesSnapshot($schemaSdl);
    }
}
