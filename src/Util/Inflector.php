<?php

namespace As3\Modlr\Util;

class Inflector
{
    /**
     * Convert word into underscore format (e.g. some_name_here).
     *
     * @param   string  $word
     * @return  string
     */
    public function underscore($word)
    {
        if (false !== stristr($word, '-')) {
            $parts = explode('-', $word);
            foreach ($parts as &$part) {
                $part = ucfirst(strtolower($part));
            }
            $word = implode('', $parts);
        }
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $word));
    }

    /**
     * Convert word into dasherized format (e.g. some-name-here).
     *
     * @param   string  $word
     * @return  string
     */
    public function dasherize($word)
    {
        return str_replace('_', '-', $this->underscore($word));
    }

    /**
     * Convert word into camelized format (e.g. someNameHere).
     *
     * @param   string  $word
     * @return  string
     */
    public function camelize($word)
    {
        return lcfirst($this->studlify($word));
    }

    /**
     * Convert word into studly caps format (e.g. SomeNameHere).
     *
     * @param   string  $word
     * @return  string
     */
    public function studlify($word)
    {
        return str_replace(" ", "", $this->wordify($word));
    }

    /**
     * Convert word into wordified format (e.g. Some Name Here).
     *
     * @param   string  $word
     * @return  string
     */
    public function wordify($word)
    {
        return ucwords(strtr($this->underscore($word), "_", " "));
    }
}
