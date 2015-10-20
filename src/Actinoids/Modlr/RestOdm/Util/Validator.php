<?php
namespace Actinoids\Modlr\RestOdm\Util;

/**
 * Responsibile for validating common components of metadata and other formats.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Validator
{
    /**
     * Valid entity string formats.
     *
     * @var array
     */
    private $stringFormats = ['dash', 'camelcase', 'studlycaps', 'underscore'];

    private $nameFormats = [
        'studlycaps'    => '/^[A-Z]{1}[a-zA-Z]{0,}$/',
        'camelcase'     => '/^[a-z]{1}[a-zA-Z]{0,}$/',
        'dash'          => '/^(?!.*--.*)[a-z]{1}[a-z-]{0,}(?<!-)$/',
        'underscore'    => '/^(?!.*__.*)[a-z]{1}[a-z_]{0,}(?<!_)$/',
    ];

    /**
     * Validates a name (such as entity types or entity field keys) to a selected format.
     *
     * @param   string  $format
     * @param   string  $name
     * @return  bool
     * @throws  InvalidArgumentException
     */
    public function validateName($format, $name)
    {
        $this->validateStringFormat($format);
        $name = iconv(mb_detect_encoding($name), 'UTF-8', $name);

        $valid = $this->nameFormats[$format];
        if (!preg_match($valid, $name)) {
            throw new InvalidArgumentException(sprintf('The name "%s" contains an invalid character.', $name));
        }
        return true;
    }

    /**
     * Validates an entity string format.
     *
     * @param   string  $format
     * @return  bool
     * @throws  InvalidArgumentException
     */
    public function validateStringFormat($format)
    {
        if (!in_array($format, $this->stringFormats)) {
            throw new InvalidArgumentException(sprintf('The string format "%s" is invalid. Valid formats are "%s"', $format, implode(', ', $this->stringFormats)));
        }
        return true;
    }
}
