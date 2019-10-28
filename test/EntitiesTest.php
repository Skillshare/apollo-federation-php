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

    public function testCreatingEntityTypeWithoutKeyFields()
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Entity key fields must be provided and has to be an array.');

        $userType = new EntityObjectType([
            'name' => 'User',
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()]
            ]
        ]);
    }

    public function testCreatingEntityTypeWithNonExistingKeyFields()
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Entity key refers to a field that does not exist in the fields array.');

        $userType = new EntityObjectType([
            'name' => 'User',
            'keyFields' => ['id', 'email'],
            'fields' => [
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()]
            ]
        ]);
    }

    public function testCreatingEntityTypeWithInvalidKeyFields()
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Entity key fields must be provided and has to be an array.');

        $userType = new EntityObjectType([
            'name' => 'User',
            'keyFields' => 'id',
            'fields' => [
                'id' => ['type' => Type::int()],
                'email' => ['type' => Type::string()],
                'firstName' => ['type' => Type::string()],
                'lastName' => ['type' => Type::string()]
            ]
        ]);
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
