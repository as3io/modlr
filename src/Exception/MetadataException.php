<?php

namespace As3\Modlr\RestOdm\Exception;

/**
 * Metadata exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MetadataException extends \Exception implements ExceptionInterface
{
    public static function mappingNotFound($entityType)
    {
        return new self(sprintf('Unable to locate metadata mapping information for Entity type "%s"', $entityType), 100);
    }

    public static function invalidMetadata($entityType, $message)
    {
        return new self(sprintf('The mapping for Entity "%s" is invalid: %s', $entityType, $message), 101);
    }

    public static function fatalDriverError($entityType, $message)
    {
        return new self(sprintf('Unable to load metadata for Entity type "%s" - a fatal driver error ocurred: %s', $entityType, $message), 102);
    }

    public static function invalidEntityType($entityType)
    {
        return new self(sprintf('The provided Entity type "%s" is invalid.', $entityType));
    }

    public static function fieldKeyInUse($attemptedKeyType, $existsKeyType, $fieldKey, $entityType)
    {
        throw new self(sprintf(
            'The %s key "%s" already exists as a(n) %s. A(n) %s cannot have the same key as a(n) %s on Entity type "%s"',
            $attemptedKeyType,
            $fieldKey,
            $existsKeyType,
            $attemptedKeyType,
            $existsKeyType,
            $entityType
        ));
    }

    public static function mixinPropertyExists($modelTypeKey, $mixinName, $propertyType, $propertyKey)
    {
        throw new self(sprintf(
            'Unable to add %s key "%s" from mixin "%s" to model "%s" -- the property already exists on the model.',
            $propertyType,
            $propertyKey,
            $mixinName,
            $modelTypeKey
        ));
    }

    public static function reservedFieldKey($key, array $reserved)
    {
        throw new self(sprintf('The field key "%s" is reserved and cannot be used. Reserved keys are "%s"', $key, implode(', ', $reserved)));
    }

    public static function invalidRelType($relType, array $valid)
    {
        throw new self(sprintf('The relationship type "%s" is not valid. Valid types are "%s"', $relType, implode(', ', $valid)));
    }
}
