<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Driver;

use Actinoids\Modlr\RestOdm\Metadata;
use Actinoids\Modlr\RestOdm\Exception\RuntimeException;
use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Symfony\Component\Yaml\Yaml;

/**
 * The YAML metadata file driver.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class YamlFileDriver extends AbstractFileDriver
{
    /**
     * An in-memory cache of parsed metadata mappings (from file).
     *
     * @var array
     */
    private $mappings = [];

    /**
     * {@inheritDoc}
     */
    protected function loadMetadataFromFile($type, $file)
    {
        $mapping = $this->getMapping($type, $file);

        $metadata = new Metadata\EntityMetadata($type);

        if (isset($mapping['entity']['db'])) {
            $metadata->db = $mapping['entity']['db'];
        }

        if (isset($mapping['entity']['collection'])) {
            $metadata->collection = $mapping['entity']['collection'];
        }

        if (isset($mapping['entity']['abstract'])) {
            $metadata->setAbstract(true);
        }

        if (isset($mapping['entity']['polymorphic'])) {
            $metadata->setPolymorphic(true);
            $metadata->ownedTypes = $this->getOwnedTypes($metadata->type);
        }

        if (isset($mapping['entity']['extends'])) {
            $metadata->extends = $mapping['entity']['extends'];
        }

        $this->setAttributes($metadata, $mapping['attributes']);
        $this->setRelationships($metadata, $mapping['relationships']);
        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeHierarchy($type, array $types = [])
    {
        $path = $this->getFilePathForType($type);
        $mapping = $this->getMapping($type, $path);

        $types[] = $type;
        if (isset($mapping['entity']['extends']) && $mapping['entity']['extends'] !== $type) {
            return $this->getTypeHierarchy($mapping['entity']['extends'], $types);
        }
        return array_reverse($types);
    }

    /**
     * {@inheritDoc}
     */
    public function getOwnedTypes($type, array $types = [])
    {
        $all = $this->getAllTypeNames();
        foreach ($this->getAllTypeNames() as $searchType) {
            if ($type === $searchType) {
                continue;
            }
            if (0 !== strpos($searchType, $type)) {
                continue;
            }

            $path = $this->getFilePathForType($searchType);
            $mapping = $this->getMapping($searchType, $path);

            if (!isset($mapping['entity']['extends']) || $mapping['entity']['extends'] !== $type) {
                continue;
            }
            $types[] = $searchType;

        }
        return $types;
    }

    /**
     * Gets the metadata mapping information from the YAML file.
     *
     * @param   string  $type
     * @param   string  $file
     * @return  array
     * @throws  MetadataException
     */
    private function getMapping($type, $file)
    {
        if (isset($this->mappings[$type])) {
            // Set to array cache to prevent multiple gets/parses.
            return $this->mappings[$type];
        }

        $contents = Yaml::parse(file_get_contents($file));
        if (!isset($contents[$type])) {
            throw MetadataException::fatalDriverError($type, sprintf('No type key was found at the beginning of the YAML file. Expected "%s"', $type));
        }
        return $this->mappings[$type] = $this->setDefaults($contents[$type]);
    }

    /**
     * Sets the entity attribute metadata from the metadata mapping.
     *
     * @todo    Inject type manager and validate data type. Or should this happen later???
     * @todo    Add support for complex attributes, like arrays and objects.
     * @param   Metadata\AttributeInterface $metadata
     * @param   array                       $attrMapping
     * @return  Metadata\EntityMetadata
     */
    protected function setAttributes(Metadata\AttributeInterface $metadata, array $attrMapping)
    {
        foreach ($attrMapping as $key => $mapping) {
            if (!is_array($mapping)) {
                $mapping = ['type' => null];
            }

            if (!isset($mapping['type'])) {
                $mapping['type'] = null;
            }

            // $this->validator->validateDataType($mapping['type']);
            switch ($mapping['type']) {
                // case 'object':
                //     $childMapping = (isset($mapping['attributes']) && is_array($mapping['attributes'])) ? $mapping['attributes'] : [];
                //     $attribute = new Metadata\ObjectAttributeMetadata($key, $mapping['type']);
                //     $this->setAttributes($attribute, $childMapping);
                //     break;
                // case 'array':
                //     $valuesType = isset($mapping['valuesType']) ? $mapping['valuesType'] : 'string';
                //     $this->validator->validateDataType($valuesType);
                //     $attribute = new Metadata\ArrayAttributeMetadata($key, $mapping['type'], $valuesType);
                //     break;
                default:
                    $attribute = new Metadata\AttributeMetadata($key, $mapping['type']);
                    break;
            }

            if (isset($mapping['description'])) {
                $attribute->description = $mapping['description'];
            }

            $metadata->addAttribute($attribute);
        }
        return $metadata;
    }

    /**
     * Sets the entity relationship metadata from the metadata mapping.
     *
     * @param   Metadata\EntityMetadata $metadata
     * @param   array                   $relMapping
     * @return  Metadata\EntityMetadata
     * @throws  RuntimeException If the related entity type was not found.
     */
    protected function setRelationships(Metadata\EntityMetadata $metadata, array $relMapping)
    {
        $allTypes = $this->getAllTypeNames();
        foreach ($relMapping as $key => $mapping) {
            if (!is_array($mapping)) {
                $mapping = ['type' => null, 'entity' => null];
            }

            if (!isset($mapping['type'])) {
                $mapping['type'] = null;
            }

            if (!isset($mapping['entity'])) {
                $mapping['entity'] = null;
            }

            // if (!in_array($mapping['entity'], $allTypes)) {
            //     throw new RuntimeException(sprintf('No YAML mapping file was found for related entity type "%s" as found on relationship field "%s::%s"', $mapping['entity'], $metadata->type, $key));
            // }

            $relationship = new Metadata\RelationshipMetadata($key, $mapping['type'], $mapping['entity']);

            if (isset($mapping['description'])) {
                $relationship->description = $mapping['description'];
            }

            $metadata->addRelationship($relationship);
        }
        return $metadata;
    }

    /**
     * Sets default values to the metadata mapping array.
     *
     * @param   mixed   $mapping
     * @return  array
     */
    protected function setDefaults($mapping)
    {
        if (!is_array($mapping)) {
            $mapping = [];
        }
        foreach (['entity', 'attributes', 'relationships'] as $key) {
            if (!isset($mapping[$key]) || !is_array($mapping[$key])) {
                $mapping[$key] = [];
            }
        }
        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtension()
    {
        return 'yml';
    }
}
