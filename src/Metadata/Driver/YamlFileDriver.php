<?php

namespace As3\Modlr\Metadata\Driver;

use As3\Modlr\Metadata;
use As3\Modlr\Exception\RuntimeException;
use As3\Modlr\Exception\MetadataException;
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
        'embed' => [],
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

        if (isset($mapping['entity']['defaultValues']) && is_array($mapping['entity']['defaultValues'])) {
            $metadata->defaultValues = $mapping['entity']['defaultValues'];
        }

        $this->setPersistence($metadata, $mapping['entity']['persistence']);
        $this->setSearch($metadata, $mapping['entity']['search']);
        $this->setAttributes($metadata, $mapping['attributes']);
        $this->setRelationships($metadata, $mapping['relationships']);
        $this->setEmbeds($metadata, $mapping['embeds']);
        $this->setMixins($metadata, $mapping['mixins']);
        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadEmbedFromFile($embedName, $file)
    {
        $mapping = $this->getMapping('embed', $embedName, $file);

        $embed = new Metadata\EmbedMetadata($embedName);
        $this->setAttributes($embed, $mapping['attributes']);
        $this->setEmbeds($embed, $mapping['embeds']);
        $this->setMixins($embed, $mapping['mixins']);
        return $embed;
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

        $persistence = $factory->createInstance($mapping);

        $metadata->setPersistence($persistence);
        return $metadata;
    }

    /**
     * Sets the entity search metadata from the metadata mapping.
     *
     * @param   Metadata\EntityMetadata     $metadata
     * @param   array                       $mapping
     * @return  Metadata\EntityMetadata
     */
    protected function setSearch(Metadata\EntityMetadata $metadata, array $mapping)
    {
        $clientKey = isset($mapping['key']) ? $mapping['key'] : null;
        if (null === $clientKey) {
            // Search is not enabled for this model.
            return $metadata;
        }

        $factory = $this->getSearchMetadataFactory($clientKey);

        $search = $factory->createInstance($mapping);

        $metadata->setSearch($search);
        return $metadata;
    }

    /**
     * Sets the entity attribute metadata from the metadata mapping.
     *
     * @param   Metadata\Interfaces\AttributeInterface  $metadata
     * @param   array                                   $attrMapping
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

            if (!isset($mapping['search'])) {
                $mapping['search'] = [];
            }

            $attribute = new Metadata\AttributeMetadata($key, $mapping['type'], $this->isMixin($metadata));

            if (isset($mapping['description'])) {
                $attribute->description = $mapping['description'];
            }

            if (isset($mapping['defaultValue'])) {
                $attribute->defaultValue = $mapping['defaultValue'];
            }

            if (isset($mapping['calculated']) && is_array($mapping['calculated'])) {
                $calculated = $mapping['calculated'];
                if (isset($calculated['class']) && isset($calculated['method'])) {
                    $attribute->calculated['class']  =  $calculated['class'];
                    $attribute->calculated['method'] =  $calculated['method'];
                }
            }

            if (isset($mapping['search']['autocomplete'])) {
                $attribute->setAutocomplete(true);
            } else if (isset($mapping['search']['store'])) {
                $attribute->setSearchProperty(true);
            }

            if (isset($mapping['save'])) {
                $attribute->enableSave($mapping['save']);
            }

            if (isset($mapping['serialize'])) {
                $attribute->enableSerialize($mapping['serialize']);
            }

            $metadata->addAttribute($attribute);
        }
        return $metadata;
    }

    /**
     * Sets the entity embed metadata from the metadata mapping.
     *
     * @param   Metadata\Interfaces\EmbedInterface  $metadata
     * @param   array                               $embedMapping
     * @return  Metadata\EntityMetadata
     */
    protected function setEmbeds(Metadata\Interfaces\EmbedInterface $metadata, array $embedMapping)
    {
        foreach ($embedMapping as $key => $mapping) {
            if (!is_array($mapping)) {
                $mapping = ['type' => null, 'entity' => null];
            }

            if (!isset($mapping['type'])) {
                $mapping['type'] = null;
            }

            if (!isset($mapping['entity'])) {
                $mapping['entity'] = null;
            }

            $embedMeta = $this->loadMetadataForEmbed($mapping['entity']);
            if (null === $embedMeta) {
                continue;
            }
            $property = new Metadata\EmbeddedPropMetadata($key, $mapping['type'], $embedMeta, $this->isMixin($metadata));

            if (isset($mapping['serialize'])) {
                $property->enableSerialize($mapping['serialize']);
            }

            $metadata->addEmbed($property);
        }
        return $metadata;
    }

    /**
     * Sets creates mixin metadata instances from a set of mixin mappings ands sets them to the entity metadata instance.
     *
     * @param   Metadata\Interfaces\MixinInterface  $metadata
     * @param   array                   $mixins
     * @return  Metadata\Interfaces\MixinInterface
     */
    protected function setMixins(Metadata\Interfaces\MixinInterface $metadata, array $mixins)
    {
        foreach ($mixins as $mixinName) {
            $mixinMeta = $this->loadMetadataForMixin($mixinName);
            if (null === $mixinMeta) {
                continue;
            }
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

            if (!isset($mapping['search'])) {
                $mapping['search'] = [];
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

            if (isset($mapping['search']['store'])) {
                $relationship->setSearchProperty(true);
            }

            if (isset($mapping['save'])) {
                $relationship->enableSave($mapping['save']);
            }

            if (isset($mapping['serialize'])) {
                $attribute->enableSerialize($mapping['serialize']);
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

        $mapping = $this->setRootDefault('attributes', $mapping);
        $mapping = $this->setRootDefault('embeds', $mapping);
        $mapping = $this->setRootDefault('mixins', $mapping);
        if ('embed' === $metaType) {
            return $mapping;
        }

        $mapping = $this->setRootDefault('relationships', $mapping);
        if ('mixin' === $metaType) {
            return $mapping;
        }
        $this->setRootDefault('entity', $mapping);

        if (!isset($mapping['entity']['persistence']) || !is_array($mapping['entity']['persistence'])) {
            $mapping['entity']['persistence'] = [];
        }

        if (!isset($mapping['entity']['search']) || !is_array($mapping['entity']['search'])) {
            $mapping['entity']['search'] = [];
        }
        return $mapping;
    }

    /**
     * Sets a root level default value to a metadata mapping array.
     *
     * @param   string  $key
     * @param   array   $mapping
     * @return  array
     */
    private function setRootDefault($key, array $mapping)
    {
        if (!isset($mapping[$key]) || !is_array($mapping[$key])) {
            $mapping[$key] = [];
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
