<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

use GraphQL\Type\Definition\Type;
use GraphQL\Error\InvariantViolation;

use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;

class EntitiesTest extends TestCase
{
    use MatchesSnapshots;

    public function testCreatingEntityType()
    {
        $userTypeKeyFields = ['id', 'email'];

        $userType = new EntityObjectType([
            'name' => 'User',
            'keyFields' => $userTypeKeyFields,
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()]
            ]
        ]);

        $this->assertEqualsCanonicalizing($userType->getKeyFields(), $userTypeKeyFields);
        $this->assertMatchesSnapshot($userType->config);
    }

    public function testCreatingEntityTypeWithCallable()
    {
        $userTypeKeyFields = ['id', 'email'];

        $userType = new EntityObjectType([
            'name' => 'User',
            'keyFields' => $userTypeKeyFields,
            'fields' => function () {
                return [
                    'id' => ['type' => Type::int()],
                    'email' => ['type' => Type::string()],
                    'firstName' => ['type' => Type::string()],
                    'lastName' => ['type' => Type::string()]
                ];
            }
        ]);

        $this->assertEqualsCanonicalizing($userType->getKeyFields(), $userTypeKeyFields);
        $this->assertMatchesSnapshot($userType->config);
    }

    public function testResolvingEntityReference()
    {
        $expectedRef = [
            'id' => 1,
            'email' => 'luke@skywalker.com',
            'firstName' => 'Luke',
            'lastName' => 'Skywalker',
            '__typename' => 'User'
        ];

        $userType = new EntityObjectType([
            'name' => 'User',
            'keyFields' => ['id', 'email'],
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()]
            ],
            '__resolveReference' => function () use ($expectedRef) {
                return $expectedRef;
            }
        ]);

        $actualRef = $userType->resolveReference(['id' => 1, 'email' => 'luke@skywalker.com', '__typename' => 'User']);

        $this->assertEquals($expectedRef, $actualRef);
    }

    public function testCreatingEntityRefType()
    {
        $userTypeKeyFields = ['id', 'email'];

        $userType = new EntityRefObjectType([
            'name' => 'User',
            'keyFields' => $userTypeKeyFields,
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()]
            ]
        ]);

        $this->assertEqualsCanonicalizing($userType->getKeyFields(), $userTypeKeyFields);
        $this->assertMatchesSnapshot($userType->config);
    }
}
