<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;

class StarWarsSchema
{
    public static $episodesSchema;
    public static $overRiddedEpisodesSchema;

    public static function getEpisodesSchema(): FederatedSchema
    {
        if (!self::$episodesSchema) {
            self::$episodesSchema = new FederatedSchema([
                'query' => self::getQueryType()
            ]);
        }
        return self::$episodesSchema;
    }

    private static function getQueryType(): ObjectType
    {
        $episodeType = self::getEpisodeType();

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'episodes' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                    'resolve' => function () {
                        return StarWarsData::getEpisodes();
                    }
                ],
                'deprecatedEpisodes' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                    'deprecationReason' => 'Because you should use the other one.'
                ]
            ]
        ]);
        return $queryType;
    }

    private static function getEpisodeType(): EntityObjectType
    {
        return new EntityObjectType([
            'name' => 'Episode',
            'description' => 'A film in the Star Wars Trilogy',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::int())
                ],
                'title' => [
                    'type' => Type::nonNull(Type::string())
                ],
                'characters' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getCharacterType()))),
                    'resolve' => function ($root) {
                        return StarWarsData::getCharactersByIds($root['characters']);
                    },
                    'provides' => 'name'
                ]
            ],
            'keyFields' => ['id'],
            '__resolveReference' => function ($ref) {
                // print_r($ref);
                return StarWarsData::getEpisodeById($ref['id']);
            }
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
                    'isExternal' => true
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'isExternal' => true
                ],
                'locations' => [
                    'type' => Type::nonNull(Type::listOf(self::getLocationType())),
                    'resolve' => function ($root) {
                        return StarWarsData::getLocationsByIds($root['locations']);
                    },
                    'requires' => 'name'
                ]
            ],
            'keyFields' => ['id']
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
                    'isExternal' => true
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'isExternal' => true
                ]
            ],
            'keyFields' => ['id']
        ]);
    }
}
