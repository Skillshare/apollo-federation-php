<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use function array_map;

class StarWarsSchema
{
    public static FederatedSchema $episodesSchema;
    public static FederatedSchema $overRiddedEpisodesSchema;

    public static function getEpisodesSchema(): FederatedSchema
    {
        if (!isset(self::$episodesSchema)) {
            self::$episodesSchema = new FederatedSchema([
                'query' => self::getQueryType(),
            ]);
        }

        return self::$episodesSchema;
    }

    public static function getEpisodesSchemaCustomResolver(): FederatedSchema
    {
        if (!isset(self::$overRiddedEpisodesSchema)) {
            self::$overRiddedEpisodesSchema = new FederatedSchema([
                'query' => self::getQueryType(),
                'resolve' => fn ($root, $args, $context, $info) => array_map(function ($ref) use ($info) {
                        $typeName = $ref['__typename'];
                        $type = $info->schema->getType($typeName);
                        $ref['id'] = $ref['id'] + 1;

                        return $type->resolveReference($ref);
                }, $args['representations']),
            ]);
        }

        return self::$overRiddedEpisodesSchema;
    }

    private static function getQueryType(): ObjectType
    {
        $episodeType = self::getEpisodeType();

        return new ObjectType([
            'name' => 'Query',
            'fields' => [
                'episodes' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                    'resolve' => fn () => StarWarsData::getEpisodes(),
                ],
                'deprecatedEpisodes' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                    'deprecationReason' => 'Because you should use the other one.',
                ],
            ],
        ]);
    }

    private static function getEpisodeType(): EntityObjectType
    {
        return new EntityObjectType([
            'name' => 'Episode',
            'description' => 'A film in the Star Wars Trilogy',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::int()),
                ],
                'title' => [
                    'type' => Type::nonNull(Type::string()),
                ],
                'characters' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getCharacterType()))),
                    'resolve' => fn ($root) => StarWarsData::getCharactersByIds($root['characters']),
                    'provides' => 'name',
                ],
            ],
            'keyFields' => ['id'],
            '__resolveReference' => function ($ref) {
                // print_r($ref);
                $entity = StarWarsData::getEpisodeById($ref['id']);
                $entity['__typename'] = 'Episode';

                return $entity;
            },
        ]);
    }

    private static function getCharacterType(): EntityRefObjectType
    {
        return new EntityRefObjectType([
            'name' => 'Character',
            'description' => 'A character in the Star Wars Trilogy',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::int()),
                    'isExternal' => true,
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'isExternal' => true,
                ],
                'locations' => [
                    'type' => Type::nonNull(Type::listOf(self::getLocationType())),
                    'resolve' => fn ($root) => StarWarsData::getLocationsByIds($root['locations']),
                    'requires' => 'name',
                ],
            ],
            'keyFields' => ['id'],
        ]);
    }

    private static function getLocationType(): EntityRefObjectType
    {
        return new EntityRefObjectType([
            'name' => 'Location',
            'description' => 'A location in the Star Wars Trilogy',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::int()),
                    'isExternal' => true,
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'isExternal' => true,
                ],
            ],
            'keyFields' => ['id'],
        ]);
    }
}
