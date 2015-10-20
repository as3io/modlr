<?php

namespace Actinoids\Modlr\RestOdm\Util;

use Actinoids\Modlr\RestOdm\Exception\RuntimeException;
use Actinoids\Modlr\RestOdm\Rest\RestConfiguration;

/**
 * Responsibile for formatting entity names, such as entity types and field keys.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class NameFormatter
{
    private $config;

    private $inflector;

    public function __construct(RestConfiguration $config)
    {
        $this->config = $config;
        $this->inflector = new Inflector();
    }

    public function getValidator()
    {
        return $this->config->getValidator();
    }

    public function validateFieldKey($value)
    {
        return $this->getValidator()->validateName($this->config->getFieldKeyFormat(), $value);
    }

    public function formatEntityType($type)
    {
        return $this->formatValue($this->config->getEntityFormat(), $type);
    }

    public function formatFieldKey($key)
    {
        return $this->formatValue($this->config->getFieldKeyFormat(), $key);
    }

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
