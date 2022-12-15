<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use function array_values;

class SchemaTest extends TestCase
{
    use MatchesSnapshots;

    public function testRunningQueries(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $query = 'query GetEpisodes { episodes { id title characters { id name } } }';

        $result = GraphQL::executeQuery($schema, $query);

        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testEntityTypes(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();

        $entityTypes = $schema->getEntityTypes();
        $hasEntityTypes = $schema->hasEntityTypes();

        $this->assertTrue($hasEntityTypes);
        $this->assertArrayHasKey('Episode', $entityTypes);
        $this->assertArrayHasKey('Character', $entityTypes);
        $this->assertArrayHasKey('Location', $entityTypes);
    }

    public function testMetaTypes(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();

        $anyType = $schema->getType('_Any');

        /** @var UnionType $entitiesType */
        $entitiesType = $schema->getType('_Entity');

        $this->assertNotNull($anyType);
        $this->assertNotNull($entitiesType);
        $this->assertEqualsCanonicalizing($entitiesType->getTypes(), array_values($schema->getEntityTypes()));
    }

    public function testDirectives(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $directives = $schema->getDirectives();

        $this->assertArrayHasKey('key', $directives);
        $this->assertArrayHasKey('external', $directives);
        $this->assertArrayHasKey('provides', $directives);
        $this->assertArrayHasKey('requires', $directives);

        // These are the default directives, which should not be replaced
        // https://www.apollographql.com/docs/apollo-server/schema/directives#default-directives
        $this->assertArrayHasKey('include', $directives);
        $this->assertArrayHasKey('skip', $directives);
        $this->assertArrayHasKey('deprecated', $directives);
    }

    public function testServiceSdl(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $query = 'query GetServiceSdl { _service { sdl } }';

        $result = GraphQL::executeQuery($schema, $query);

        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testSchemaSdl(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $schemaSdl = SchemaPrinter::doPrint($schema);

        $this->assertMatchesSnapshot($schemaSdl);
    }

    public function testResolvingEntityReferences(): void
    {
        $schema = StarWarsSchema::getEpisodesSchema();

        $query = '
            query GetEpisodes($representations: [_Any!]!) {
                _entities(representations: $representations) {
                    ... on Episode {
                        id
                        title
                    }
                }
            }
        ';

        $variables = [
            'representations' => [
                [
                    '__typename' => 'Episode',
                    'id' => 1,
                ],
            ],
        ];

        $result = GraphQL::executeQuery($schema, $query, null, null, $variables);

        /** @var mixed[] $entities */
        $entities = $result->data['_entities'] ?? [];

        $this->assertCount(1, $entities);
        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testOverrideSchemaResolver(): void
    {
        $schema = StarWarsSchema::getEpisodesSchemaCustomResolver();

        $query = '
            query GetEpisodes($representations: [_Any!]!) {
                _entities(representations: $representations) {
                    ... on Episode {
                        id
                        title
                    }
                }
            }
        ';

        $variables = [
            'representations' => [
                [
                    '__typename' => 'Episode',
                    'id' => 1,
                ],
            ],
        ];

        $result = GraphQL::executeQuery($schema, $query, null, null, $variables);

        /** @var array{_entities: array<array{title: string}>} $data */
        $data = $result->data;

        // The custom resolver for this schema, always adds 1 to the id and gets the next
        // episode for the sake of testing the ability to change the resolver in the configuration
        $this->assertEquals('The Empire Strikes Back', $data['_entities'][0]['title']);
        $this->assertMatchesSnapshot($result->toArray());
    }
}
