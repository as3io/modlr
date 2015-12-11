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
final class YamlFileDriver extends AbstractFileDriver
{
    /**
     * An in-memory cache of parsed metadata mappings (from file).
     *
     * @var array
     */
    private $mappings = [
        'model' => [],
        'mixin' => [],
    ];

    /**
     * {@inheritDoc}
     */
    protected function loadMetadataFromFile($type, $file)
    {
        $mapping = $this->getMapping('model', $type, $file);

        $metadata = new Metadata\EntityMetadata($type);

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

        $this->setPersistence($metadata, $mapping['entity']['persistence']);
        $this->setAttributes($metadata, $mapping['attributes']);
        $this->setRelationships($metadata, $mapping['relationships']);
        $this->setMixins($metadata, $mapping['mixins']);
        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMixinFromFile($mixinName, $file)
    {
        $mapping = $this->getMapping('mixin', $mixinName, $file);

        $mixin = new Metadata\MixinMetadata($mixinName);

        $this->setAttributes($mixin, $mapping['attributes']);
        $this->setRelationships($mixin, $mapping['relationships']);
        return $mixin;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeHierarchy($type, array $types = [])
    {
        $path = $this->getFilePath('model', $type);
        $mapping = $this->getMapping('model', $type, $path);

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
        $path = $this->getFilePath('model', $type);
        $superMapping = $this->getMapping('model', $type, $path);

        $abstract = isset($superMapping['entity']['abstract']) && true === $superMapping['entity']['abstract'];

        foreach ($this->getAllTypeNames() as $searchType) {

            if ($type === $searchType && false === $abstract) {
                $types[] = $type;
                continue;
            }
            if (0 !== strpos($searchType, $type)) {
                continue;
            }

            $path = $this->getFilePath('model', $searchType);
            $mapping = $this->getMapping('model', $searchType, $path);

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
     * @param   string  $metaType   The metadata type, either mixin or model.
     * @param   string  $key        The metadata key name, either the mixin name or model type.
     * @param   string  $file       The YAML file location.
     * @return  array
     * @throws  MetadataException
     */
    private function getMapping($metaType, $key, $file)
    {
        if (isset($this->mappings[$metaType][$key])) {
            // Set to array cache to prevent multiple gets/parses.
            return $this->mappings[$metaType][$key];
        }

        $contents = Yaml::parse(file_get_contents($file));
        if (!isset($contents[$key])) {
            throw MetadataException::fatalDriverError($key, sprintf('No mapping key was found at the beginning of the YAML file. Expected "%s"', $key));
        }
        return $this->mappings[$metaType][$key] = $this->setDefaults($metaType, $contents[$key]);
    }

    /**
     * Sets the entity persistence metadata from the metadata mapping.
     *
     * @param   Metadata\EntityMetadata     $metadata
     * @param   array                       $mapping
     * @return  Metadata\EntityMetadata
     */
    protected function setPersistence(Metadata\EntityMetadata $metadata, array $mapping)
    {
        $persisterKey = isset($mapping['key']) ? $mapping['key'] : null;
        $factory = $this->getPersistenceMetadataFactory($persisterKey);

        $persistence = $factory->getNewInstance();
        $persistence->persisterKey = $persisterKey;

        if (isset($mapping['db'])) {
            $persistence->db = $mapping['db'];
        }

        if (isset($mapping['collection'])) {
            $persistence->collection = $mapping['collection'];
        }

        $metadata->setPersistence($persistence);
        return $metadata;
    }

    /**
     * Sets the entity attribute metadata from the metadata mapping.
     *
     * @todo    Inject type manager and validate data type. Or should this happen later???
     * @todo    Add support for complex attributes, like arrays and objects.
     * @param   Metadata\Interfaces\AttributeInterface $metadata
     * @param   array                       $attrMapping
     * @return  Metadata\EntityMetadata
     */
    protected function setAttributes(Metadata\Interfaces\AttributeInterface $metadata, array $attrMapping)
    {
        foreach ($attrMapping as $key => $mapping) {
            if (!is_array($mapping)) {
                $mapping = ['type' => null];
            }

            if (!isset($mapping['type'])) {
                $mapping['type'] = null;
            }

            $attribute = new Metadata\AttributeMetadata($key, $mapping['type'], $this->isMixin($metadata));

            // @todo Handle complex attribute types.
            if (isset($mapping['description'])) {
                $attribute->description = $mapping['description'];
            }

            $metadata->addAttribute($attribute);
        }
        return $metadata;
    }

    protected function setMixins(Metadata\EntityMetadata $metadata, array $mixins)
    {
        foreach ($mixins as $mixinName) {
            $mixinMeta = $this->loadMetadataForMixin($mixinName);
            $metadata->addMixin($mixinMeta);
        }
        return $metadata;
    }

    /**
     * Sets the entity relationship metadata from the metadata mapping.
     *
     * @param   Metadata\Interfaces\RelationshipInterface   $metadata
     * @param   array                                       $relMapping
     * @return  Metadata\Interfaces\RelationshipInterface
     * @throws  RuntimeException If the related entity type was not found.
     */
    protected function setRelationships(Metadata\Interfaces\RelationshipInterface $metadata, array $relMapping)
    {
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

            $relationship = new Metadata\RelationshipMetadata($key, $mapping['type'], $mapping['entity'], $this->isMixin($metadata));

            if (isset($mapping['description'])) {
                $relationship->description = $mapping['description'];
            }

            if (isset($mapping['inverse'])) {
                $relationship->isInverse = true;
                if (isset($mapping['field'])) {
                    $relationship->inverseField = $mapping['field'];
                }
            }

            $path = $this->getFilePath('model', $mapping['entity']);
            $relatedEntityMapping = $this->getMapping('model', $mapping['entity'], $path);

            if (isset($relatedEntityMapping['entity']['polymorphic'])) {
                $relationship->setPolymorphic(true);
                $relationship->ownedTypes = $this->getOwnedTypes($mapping['entity']);
            }

            $metadata->addRelationship($relationship);
        }
        return $metadata;
    }

    /**
     * Determines if a metadata instance is a mixin.
     *
     * @param   Metadata\Interfaces\PropertyInterface   $metadata
     * @return  bool
     */
    protected function isMixin(Metadata\Interfaces\PropertyInterface $metadata)
    {
        return $metadata instanceof Metadata\MixinMetadata;
    }

    /**
     * Sets default values to the metadata mapping array.
     *
     * @param   string  $metaType   The metadata type, either model or mixin.
     * @param   mixed   $mapping    The parsed mapping data.
     * @return  array
     */
    protected function setDefaults($metaType, $mapping)
    {
        if (!is_array($mapping)) {
            $mapping = [];
        }

        foreach (['attributes', 'relationships'] as $key) {
            if (!isset($mapping[$key]) || !is_array($mapping[$key])) {
                $mapping[$key] = [];
            }
        }

        if ('mixin' === $metaType) {
            return $mapping;
        }

        foreach (['entity', 'mixins'] as $key) {
            if (!isset($mapping[$key]) || !is_array($mapping[$key])) {
                $mapping[$key] = [];
            }
        }

        if (!isset($mapping['entity']['persistence']) || !is_array($mapping['entity']['persistence'])) {
            $mapping['entity']['persistence'] = [];
        }

        if (!isset($mapping['entity']['persistence']['key'])) {
            // @todo Should this be defaulted here, or handled differently?
            $mapping['entity']['persistence']['key'] = 'mongodb';
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
