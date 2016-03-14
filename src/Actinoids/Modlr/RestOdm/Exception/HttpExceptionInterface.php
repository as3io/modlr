<?php

namespace As3\Modlr\RestOdm\Exception;

/**
 * HTTP Response friendly Exception interface.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface HttpExceptionInterface extends ExceptionInterface
{
    /**
     * Gets the HTTP response code.
     *
     * @return  int
     */
    public function getHttpCode();
}
