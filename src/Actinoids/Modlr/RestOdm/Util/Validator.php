<?php

namespace As3\Modlr\RestOdm\Util;

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

    /**
     * Valid regex patterns per name format.
     *
     * @var array
     */
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
     */
    public function isNameValid($format, $name)
    {
        if (false === $this->isFormatValid($format)) {
            return false;
        }
        $name = iconv(mb_detect_encoding($name), 'UTF-8', $name);

        $valid = $this->nameFormats[$format];
        return !preg_match($valid, $name) ? false : true;
    }

    /**
     * Validates an entity string format.
     *
     * @param   string  $format
     * @return  bool
     */
    public function isFormatValid($format)
    {
        return in_array($format, $this->stringFormats);
    }
}
