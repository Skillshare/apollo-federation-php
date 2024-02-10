<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use Apollo\Federation\Utils\FederatedSchemaPrinter;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

/**
 * The type of the service definition required for federated schemas.
 */
class ServiceDefinitionType extends ObjectType
{
    /**
     * @param Schema $schema - the schemas whose SDL should be printed.
     */
    public function __construct(Schema $schema)
    {
        $config = [
            'name' => self::getTypeName(),
            'fields' => [
                'sdl' => [
                    'type' => Type::string(),
                    'resolve' => fn () => FederatedSchemaPrinter::doPrint($schema)
                ]
            ]
        ];
        parent::__construct($config);
    }

    public static function getTypeName(): string
    {
        return '_Service';
    }
}
