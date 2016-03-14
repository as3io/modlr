<?php

namespace As3\Modlr\RestOdm\Util;

use As3\Modlr\RestOdm\Exception\RuntimeException;
use As3\Modlr\RestOdm\Exception\MetadataException;
use As3\Modlr\RestOdm\Rest\RestConfiguration;
use As3\Modlr\RestOdm\Metadata\EntityMetadata;
use As3\Modlr\RestOdm\Metadata\MetadataFactory;
use As3\Modlr\RestOdm\DataTypes\TypeFactory;

/**
 * Responsibile for formatting entity names, such as entity types and field keys.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EntityUtility
{
    /**
     * @var RestConfiguration
     */
    private $config;

    /**
     * @var Inflector
     */
    private $inflector;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * Constructor.
     *
     * @param   RestConfiguration   $config
     */
    public function __construct(RestConfiguration $config, TypeFactory $typeFactory)
    {
        $this->config = $config;
        $this->typeFactory = $typeFactory;
        $this->inflector = new Inflector();
    }

    /**
     * Gets the validator service.
     *
     * @return  Validator
     */
    public function getValidator()
    {
        return $this->config->getValidator();
    }

    /**
     * Gets the REST configuration object.
     *
     * @return  RestConfiguration
     */
    public function getRestConfig()
    {
        return $this->config;
    }

    /**
     * Determines if a field key is valid, based on configuration.
     *
     * @param   string  $value
     * @return  bool
     */
    public function isFieldKeyValid($value)
    {
        return $this->getValidator()->isNameValid($this->config->getFieldKeyFormat(), $value);
    }

    /**
     * Determines if an entity type, based on configuration.
     *
     * @param   string  $value
     * @return  bool
     */
    public function isEntityTypeValid($value)
    {
        return $this->getValidator()->isNameValid($this->config->getEntityFormat(), $value);
    }

    /**
     * Validates EntityMetadata.
     *
     * @param   string          $requestedType
     * @param   EntityMetadata  $metadata
     * @param   MetadataFactory $mf
     * @return  bool
     * @throws  MetadataException
     */
    public function validateMetadata($requestedType, EntityMetadata $metadata, MetadataFactory $mf)
    {
        if ($requestedType !== $metadata->type) {
            throw MetadataException::invalidMetadata($requestedType, 'Metadata type mismatch.');
        }
        $this->validateMetadataType($metadata);

        if (null === $metadata->persistence) {
            throw MetadataException::invalidMetadata($requestedType, 'No persistence metadata was found. All models must use a persistence layer.');
        }

        $this->validateMetadataInheritance($metadata, $mf);
        $this->validateMetadataAttributes($metadata, $mf);
        $this->validateMetadataRelationships($metadata, $mf);
        return true;
    }

    /**
     * Validates the proper entity type on EntityMetadata.
     *
     * @param   EntityMetadata  $metadata
     * @return  bool
     * @throws  MetadataException
     */
    public function validateMetadataType(EntityMetadata $metadata)
    {
        if (true === $metadata->isChildEntity()) {
            $parentType = $metadata->getParentEntityType();
            if (0 !== strpos($metadata->type, $parentType)) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('Child class types must be prefixed by the parent class. Expected "%s" prefix.', $parentType));
            }
        }
        if (false === $this->isEntityTypeValid($metadata->type)) {
            throw MetadataException::invalidMetadata($metadata->type, sprintf('The entity type is invalid based on the configured name format "%s"', $this->config->getEntityFormat()));
        }
        return true;
    }

    /**
     * Validates the proper entity inheritance on EntityMetadata.
     *
     * @param   EntityMetadata  $metadata
     * @param   MetadataFactory $mf
     * @return  bool
     * @throws  MetadataException
     */
    public function validateMetadataInheritance(EntityMetadata $metadata, MetadataFactory $mf)
    {
        if (true === $metadata->isPolymorphic()) {
            foreach ($metadata->ownedTypes as $child) {

                if (false === $this->isEntityTypeValid($child)) {
                    throw MetadataException::invalidMetadata($metadata->type, sprintf('The owned entity type "%s" is invalid based on the configured name format "%s"', $child, $this->config->getEntityFormat()));
                }

                if (false === $mf->metadataExists($child)) {
                    throw MetadataException::invalidMetadata($metadata->type, sprintf('The entity owns a type "%s" that does not exist.', $child));
                }
            }
        }

        if (false === $metadata->isPolymorphic() && true === $metadata->isAbstract()) {
            throw MetadataException::invalidMetadata($metadata->type, 'An entity must be polymorphic to be abstract.');
        }

        if (false === $metadata->isChildEntity()) {
            return true;
        }
        if (true === $metadata->isPolymorphic()) {
            throw MetadataException::invalidMetadata($metadata->type, 'An entity cannot both be polymorphic and be a child.');
        }
        if ($metadata->extends === $metadata->type) {
            throw MetadataException::invalidMetadata($metadata->type, 'An entity cannot extend itself.');
        }

        if (false === $mf->metadataExists($metadata->extends)) {
            throw MetadataException::invalidMetadata($metadata->type, sprintf('The parent entity type "%s" does not exist.', $metadata->extends));
        }

        $parent = $mf->getMetadataForType($metadata->extends);
        if (false === $parent->isPolymorphic()) {
            throw MetadataException::invalidMetadata($metadata->type, sprintf('Parent classes must be polymorphic. Parent entity "%s" is not polymorphic.', $metadata->extends));
        }
        return true;
    }

    /**
     * Validates entity attributes on EntityMetadata.
     *
     * @param   EntityMetadata  $metadata
     * @param   MetadataFactory $mf
     * @return  bool
     * @throws  MetadataException
     */
    public function validateMetadataAttributes(EntityMetadata $metadata, MetadataFactory $mf)
    {
        foreach ($metadata->getAttributes() as $key => $attribute) {
            if ($key != $attribute->key) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('Attribute key mismtach. "%s" !== "%s"', $key, $attribute->key));
            }

            if (false === $this->isFieldKeyValid($attribute->key)) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('The attribute key "%s" is invalid based on the configured name format "%s"', $attribute->key, $this->config->getFieldKeyFormat()));
            }

            if (false === $this->typeFactory->hasType($attribute->dataType)) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('The data type "%s" for attribute "%s" is invalid', $attribute->dataType, $attribute->key));
            }

            if (true === $metadata->isChildEntity()) {
                $parent = $mf->getMetadataForType($metadata->extends);
                if ($parent->hasAttribute($attribute->key)) {
                    throw MetadataException::invalidMetadata($metadata->type, sprintf('Parent entity type "%s" already contains attribute field "%s"', $parent->type, $attribute->key));
                }
            }

            // @todo Determine how to validate object and array
            $todo = ['object', 'array'];
            if (in_array($attribute->dataType, $todo)) {
                throw MetadataException::invalidMetadata($metadata->type, 'NYI: Object and array attribute types still need expanding!!');
            }

            if (true === $attribute->isCalculated()) {
                if (false === class_exists($attribute->calculated['class']) || false === method_exists($attribute->calculated['class'], $attribute->calculated['method'])) {
                    throw MetadataException::invalidMetadata($metadata->type, sprintf('The attribute field "%s" is calculated, but was unable to find method "%s:%s"', $attribute->key, $attribute->calculated['class'], $attribute->calculated['method']));
                }
            }
        }
        return true;
    }

    /**
     * Validates entity relationships on EntityMetadata.
     *
     * @param   EntityMetadata  $metadata
     * @param   MetadataFactory $mf
     * @return  bool
     * @throws  MetadataException
     */
    public function validateMetadataRelationships(EntityMetadata $metadata, MetadataFactory $mf)
    {
        foreach ($metadata->getRelationships() as $key => $relationship) {
            if ($key != $relationship->key) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('Relationship key mismtach. "%s" !== "%s"', $key, $relationship->key));
            }
            if (false === $this->isFieldKeyValid($relationship->key)) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('The relationship key "%s" is invalid based on the configured name format "%s"', $relationship->key, $this->config->getFieldKeyFormat()));
            }
            if (true === $metadata->isChildEntity()) {
                $parent = $mf->getMetadataForType($metadata->extends);
                if ($parent->hasRelationship($relationship->key)) {
                    throw MetadataException::invalidMetadata($metadata->type, sprintf('Parent entity type "%s" already contains relationship field "%s"', $parent->type, $relationship->key));
                }
            }
            if (false === $this->isEntityTypeValid($relationship->entityType)) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('The related model "%s" for relationship "%s" is invalid based on the configured name format "%s"', $relationship->entityType, $relationship->key, $this->config->getEntityFormat()));
            }
            if (false === $mf->metadataExists($relationship->entityType)) {
                throw MetadataException::invalidMetadata($metadata->type, sprintf('The related model "%s" for relationship "%s" does not exist.', $relationship->entityType, $relationship->key));
            }
            if (true === $relationship->isInverse) {
                if ('one' === $relationship->relType) {
                    throw MetadataException::invalidMetadata($metadata->type, 'The relationship is inverse and one, which is currently not supported.');
                }
                if (empty($relationship->inverseField)) {
                    throw MetadataException::invalidMetadata($metadata->type, 'The relationship is inverse, but no inverse field was specified.');
                }
                $related = ($relationship->entityType === $metadata->type) ? $metadata : $mf->getMetadataForType($relationship->entityType);
                if (false === $related->hasRelationship($relationship->inverseField)) {
                    throw MetadataException::invalidMetadata($metadata->type, 'The relationship is inverse, but the related model does not contain the specified inverse field.');
                }
                $relatedRel = $related->getRelationship($relationship->inverseField);
                if (true === $relatedRel->isInverse) {
                    throw MetadataException::invalidMetadata($metadata->type, 'The relationship is inverse, but the relationship it references is also inverse.');
                }
                if ('one' !== $relatedRel->relType) {
                    throw MetadataException::invalidMetadata($metadata->type, 'The relationship is inverse and many, but it\'s reference is not a type of one.');
                }

            }
        }
        return true;
    }

    /**
     * Formats an entity type, based on configuration.
     *
     * @param   string  $type
     * @return  string
     */
    public function formatEntityType($type)
    {
        return $this->formatValue($this->config->getEntityFormat(), $type);
    }

    /**
     * Formats a field key, based on configuration.
     *
     * @param   string  $key
     * @return  string
     */
    public function formatFieldKey($key)
    {
        return $this->formatValue($this->config->getFieldKeyFormat(), $key);
    }

    /**
     * Formats a value, based on a formatting type.
     *
     * @param   string  $format
     * @param   string  $value
     * @return  string
     * @throws  RuntimeException
     */
    protected function formatValue($format, $value)
    {
        switch ($format) {
            case 'dash':
                return $this->inflector->dasherize($value);
            case 'underscore':
                return $this->inflector->underscore($value);
            case 'studlycaps':
                return $this->inflector->studlify($value);
            case 'camelcase':
                return $this->inflector->camelize($value);
            default:
                throw new RuntimeException('Unable to format value');
        }
    }
}
