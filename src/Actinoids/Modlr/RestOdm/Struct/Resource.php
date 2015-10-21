<?php

namespace Actinoids\Modlr\RestOdm\Struct;

class Resource
{
    use Traits\MetaEnabled;

    /**
     * The top level, primary entity type for this resource.
     *
     * @var string
     */
    protected $entityType;

    /**
     * The resource type: representing either one or many entites.
     *
     * @var string
     */
    protected $resourceType;

    /**
     * The resources's primary data.
     *
     * @var Entity|Collection|null
     */
    protected $primaryData;

    /**
     * The resource's included data.
     *
     * @var Collection|null
     */
    protected $includedData;

    /**
     * Constructor.
     *
     * @param   string  $entityType
     * @param   string  $resourceType
     */
    public function __construct($entityType, $resourceType = 'one')
    {
        $this->entityType = $entityType;
        $this->resourceType = $resourceType;
        if ($this->isMany()) {
            $this->primaryData = new Collection();
        }
    }

    /**
     * Gets the top level, primary entity type this resource represents.
     *
     * @return  string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Determines if this is an is-one resource.
     *
     * @return  bool
     */
    public function isOne()
    {
        return false === $this->isMany();
    }

    /**
     * Determines if this is an is-many resource.
     *
     * @return  bool
     */
    public function isMany()
    {
        return 'many' === $this->resourceType;
    }

    /**
     * Determines if any resource data has been applied.
     *
     * @return  bool
     */
    public function hasData()
    {
        if ($this->isOne()) {
            return null !== $this->getPrimaryData();
        }
        return 0 < count($this->getPrimaryData());
    }

    /**
     * Pushes entities to this resource.
     *
     * @param   EntityInterface    $entity
     * @return  self
     */
    public function pushData(EntityInterface $entity)
    {
        if ($this->isMany()) {
            $this->primaryData[] = $entity;
            return $this;
        }
        $this->primaryData = $entity;
        return $this;
    }

    /**
     * Gets the primary resource data.
     *
     * @return  Entity|Collection|null
     */
    public function getPrimaryData()
    {
        return $this->primaryData;
    }

    /**
     * Sets the included (side-loaded) data collection.
     *
     * @param   Collection  $included
     * @return  self
     */
    public function setIncludedData(Collection $included)
    {
        $this->includedData = $included;
        return $this;
    }

    /**
     * Gets the included (side-loaded) data collection.
     *
     * @return  Collection
     */
    public function getIncludedData()
    {
        return $this->includedData;
    }

    /**
     * Determines if included (side-loaded) data exists.
     *
     * @return  bool
     */
    public function hasIncludedData()
    {
        $included = $this->getIncludedData();
        return null !== $included && count($included) > 0;
    }
}
