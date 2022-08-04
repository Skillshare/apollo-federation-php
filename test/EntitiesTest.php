<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class EntitiesTest extends TestCase
{
    use MatchesSnapshots;

    public function testCreatingEntityType(): void
    {
        $expectedKeys = [['fields' => 'id'], ['fields' => 'email']];

        $userType = new EntityObjectType([
            'name' => 'User',
            'keys' => $expectedKeys,
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()],
            ],
        ]);

        $this->assertEqualsCanonicalizing($expectedKeys, $userType->getKeys());
        $this->assertMatchesSnapshot($userType->config);
    }

    public function testCreatingEntityTypeWithCallable(): void
    {
        $expectedKeys = [['fields' => 'id'], ['fields' => 'email']];

        $userType = new EntityObjectType([
            'name' => 'User',
            'keys' => $expectedKeys,
            'fields' => function () {
                return [
                    'id' => ['type' => Type::int()],
                    'email' => ['type' => Type::string()],
                    'firstName' => ['type' => Type::string()],
                    'lastName' => ['type' => Type::string()],
                ];
            },
        ]);

        $this->assertEqualsCanonicalizing($expectedKeys, $userType->getKeys());
        $this->assertMatchesSnapshot($userType->config);
    }

    public function testResolvingEntityReference(): void
    {
        $expectedRef = [
            'id' => 1,
            'email' => 'luke@skywalker.com',
            'firstName' => 'Luke',
            'lastName' => 'Skywalker',
            '__typename' => 'User',
        ];

        $userType = new EntityObjectType([
            'name' => 'User',
            'keys' => [['fields' => 'id'], ['fields' => 'email']],
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()],
            ],
            '__resolveReference' => function () use ($expectedRef) {
                return $expectedRef;
            },
        ]);

        $actualRef = $userType->resolveReference(['id' => 1, 'email' => 'luke@skywalker.com', '__typename' => 'User']);

        $this->assertEquals($expectedRef, $actualRef);
    }

    public function testCreatingEntityRefType(): void
    {
        $expectedKeys = [['fields' => 'id', 'resolvable' => false]];

        $userType = new EntityRefObjectType([
            'name' => 'User',
            'keys' => $expectedKeys,
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
            ],
        ]);

        $this->assertEqualsCanonicalizing($expectedKeys, $userType->getKeys());
        $this->assertMatchesSnapshot($userType->config);
    }
}
