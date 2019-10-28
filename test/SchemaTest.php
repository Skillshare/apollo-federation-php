<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Error\InvariantViolation;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;

use sizeof;

class SchemaTest extends TestCase
{
    use MatchesSnapshots;

    public function testRunningQueries()
    {
        $schema = $this->createValidTestSchema();
        $query = 'query GetViewer { viewer { id email firstName lastName } }';

        $result = GraphQL::executeQuery($schema, $query);

        $this->assertMatchesSnapshot($result->toArray());
    }

    public function testEntityTypes()
    {
        $schema = $this->createValidTestSchema();
        $entityTypes = $schema->getEntityTypes();
        $hasEntityTypes = $schema->hasEntityTypes();

        $userType = $entityTypes[0];

        $this->assertTrue($hasEntityTypes);
        $this->assertEquals($userType->toString(), 'User');
    }

    public function testDirectives()
    {
        $schema = $this->createValidTestSchema();
        $directives = $schema->getDirectives();

        $this->assertArrayHasKey('key', $directives);
        $this->assertArrayHasKey('external', $directives);
        $this->assertArrayHasKey('provides', $directives);
        $this->assertArrayHasKey('requires', $directives);
    }

    private function createValidTestSchema()
    {
        $userType = new EntityObjectType([
            'name' => 'User',
            'keyFields' => ['id', 'email'],
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()]
            ]
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'viewer' => [
                    'type' => $userType,
                    'resolve' => function () {
                        return [
                            'id' => 1,
                            'email' => 'bruce@wayneindustries.com',
                            'firstName' => 'Bruce',
                            'lastName' => 'Wayne'
                        ];
                    }
                ]
            ]
        ]);

        return new FederatedSchema([
            'query' => $queryType
        ]);
    }
}
