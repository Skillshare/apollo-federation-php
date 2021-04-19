<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\SchemaPrinter;

use Apollo\Federation\Tests\StarWarsSchema;
use Apollo\Federation\Tests\DungeonsAndDragonsSchema;

class SchemaTest extends TestCase
{
    use MatchesSnapshots;

    public function testRunningQueries()
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $query = 'query GetEpisodes { episodes { id title characters { id name } } }';

        $result = GraphQL::executeQuery($schema, $query);

        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testEntityTypes()
    {
        $schema = StarWarsSchema::getEpisodesSchema();

        $entityTypes = $schema->getEntityTypes();
        $hasEntityTypes = $schema->hasEntityTypes();

        $this->assertTrue($hasEntityTypes);
        $this->assertArrayHasKey('Episode', $entityTypes);
        $this->assertArrayHasKey('Character', $entityTypes);
        $this->assertArrayHasKey('Location', $entityTypes);
    }

    public function testMetaTypes()
    {
        $schema = StarWarsSchema::getEpisodesSchema();

        $anyType = $schema->getType('_Any');
        $entitiesType = $schema->getType('_Entity');

        $this->assertNotNull($anyType);
        $this->assertNotNull($entitiesType);
        $this->assertEqualsCanonicalizing($entitiesType->getTypes(), array_values($schema->getEntityTypes()));
    }

    public function testDirectives()
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $directives = $schema->getDirectives();

        $this->assertArrayHasKey('key', $directives);
        $this->assertArrayHasKey('external', $directives);
        $this->assertArrayHasKey('provides', $directives);
        $this->assertArrayHasKey('requires', $directives);
    }

    public function testServiceSdl()
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $query = 'query GetServiceSdl { _service { sdl } }';

        $result = GraphQL::executeQuery($schema, $query);

        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testSchemaSdl()
    {
        $schema = StarWarsSchema::getEpisodesSchema();
        $schemaSdl = SchemaPrinter::doPrint($schema);

        $this->assertMatchesSnapshot($schemaSdl);
    }

    public function testResolvingEntityReferences()
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
                    'id' => 1
                ]
            ]
        ];

        $result = GraphQL::executeQuery($schema, $query, null, null, $variables);

        $this->assertCount(1, $result->data['_entities']);
        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testOverrideSchemaResolver()
    {
        $schema = DungeonsAndDragonsSchema::getSchema();

        $query = '
            query GetMonsters($representations: [_Any!]!) { 
                _entities(representations: $representations) {
                    ... on Monster {
                        id
                        name
                        skills {
                            name
                        }
                    }
                } 
            }
        ';

        $variables = [
            'representations' => [
                [
                    '__typename' => 'Monster',
                    'id' => 1
                ],
                [
                    '__typename' => 'Monster',
                    'id' => 2
                ],
                [
                    '__typename' => 'Monster',
                    'id' => 3
                ],
            ]
        ];

        $result = GraphQL::executeQuery($schema, $query, null, null, $variables);
        $this->assertNotNull($result->data);
        
        $entities = $result->data['_entities'];
        $this->assertCount(3, $entities);
        
        $entity = $entities[0];
        foreach ($entities as $entity) {
            $this->assertArrayHasKey('id', $entity);
            $this->assertArrayHasKey('name', $entity);
            $this->assertArrayNotHasKey('characterRating', $entity);
            $this->assertArrayHasKey('skills', $entity);

            $this->assertCount(2, $entity['skills']);
        }

        $this->assertMatchesSnapshot($result->toArray());
    }
}
 