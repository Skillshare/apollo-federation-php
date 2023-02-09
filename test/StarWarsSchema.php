<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Apollo\Federation\Enum\DirectiveEnum;
use Apollo\Federation\FederatedSchema;
use Apollo\Federation\SchemaBuilder;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class StarWarsSchema
{
    /**
     * @var FederatedSchema|null
     */
    public static $episodesSchema;

    /**
     * @var FederatedSchema|null
     */
    public static $overriddenEpisodesSchema;

    public static function getEpisodesSchema(): FederatedSchema
    {
        if (!self::$episodesSchema) {
            self::$episodesSchema = (new SchemaBuilder())->build([
                'directives' => Directive::getInternalDirectives(),
                'query' => self::getQueryType(),
            ], [
                'directives' => DirectiveEnum::getAll(),
            ]);
        }

        return self::$episodesSchema;
    }

    public static function getEpisodesSchemaCustomResolver(): FederatedSchema
    {
        if (!self::$overriddenEpisodesSchema) {
            self::$overriddenEpisodesSchema = (new SchemaBuilder())->build([
                'directives' => Directive::getInternalDirectives(),
                'query' => self::getQueryType(),
                'resolve' => function ($root, $args, $context, $info): array {
                    return array_map(static function (array $ref) use ($info) {
                        $typeName = $ref['__typename'];
                        $type = $info->schema->getType($typeName);
                        ++$ref['id'];

                        return $type->resolveReference($ref);
                    }, $args[FederatedSchema::RESERVED_FIELD_REPRESENTATIONS]);
                },
            ], [
                'directives' => DirectiveEnum::getAll(),
            ]);
        }

        return self::$overriddenEpisodesSchema;
    }

    private static function getQueryType(): ObjectType
    {
        $episodeType = self::getEpisodeType();

        return new ObjectType([
            'name' => FederatedSchema::RESERVED_TYPE_QUERY,
            'fields' => [
                'episodes' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                    'resolve' => static function (): array {
                        return StarWarsData::getEpisodes();
                    },
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
                    'resolve' => static function ($root): array {
                        return StarWarsData::getCharactersByIds($root['characters']);
                    },
                    'provides' => 'name',
                ],
            ],
            EntityObjectType::FIELD_KEYS => [['fields' => 'id']],
            EntityObjectType::FIELD_REFERENCE_RESOLVER => static function (array $ref): array {
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
                    'resolve' => static function ($root): array {
                        return StarWarsData::getLocationsByIds($root['locations']);
                    },
                    'requires' => 'name',
                ],
            ],
            EntityObjectType::FIELD_KEYS => [['fields' => 'id', 'resolvable' => false]],
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
            EntityObjectType::FIELD_KEYS => [['fields' => 'id', 'resolvable' => false]],
        ]);
    }
}
