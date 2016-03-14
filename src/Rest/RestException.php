<?php

namespace As3\Modlr\Rest;

use As3\Modlr\Exception\AbstractHttpException;

/**
 * REST Exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RestException extends AbstractHttpException
{
    public static function invalidEndpoint($path)
    {
        return new self(
            sprintf(
                'The provided path "%s" is not a valid API endpoint.',
                $path
            ),
            400,
            __FUNCTION__
        );
    }

    public static function invalidRelationshipEndpoint($path)
    {
        return new self(
            sprintf(
                'The provided path "%s" is not a valid relationship API endpoint.',
                $path
            ),
            400,
            __FUNCTION__
        );
    }

    public static function invalidRequestType($type, $supported)
    {
        return new self(
            sprintf(
                'The API request type "%s" is invalid. Valid request types are: "%s"',
                $type,
                implode(', ', $supported)
            ),
            400,
            __FUNCTION__
        );
    }

    public static function unsupportedQueryParam($param, array $supported)
    {
        return new self(
            sprintf(
                'The query parameter "%s" is not supported. Supported parameters are "%s"',
                $param,
                implode(', ', $supported)
            ),
            400,
            __FUNCTION__
        );
    }

    public static function invalidQueryParam($param, $message = null)
    {
        return new self(
            sprintf(
                'The query parameter "%s" is invalid. %s',
                $param,
                $message
            ),
            400,
            __FUNCTION__
        );
    }

    public static function invalidParamValue($param, $message = null)
    {
        return new self(
            sprintf(
                'The query parameter "%s" is invalid. %s',
                $param,
                $message
            ),
            400,
            __FUNCTION__
        );
    }

    public static function noAdapterFound($requestType)
    {
        return new self(
            sprintf(
                'No REST adapter was found to handle "%s" requests.',
                $requestType
            ),
            400,
            __FUNCTION__
        );
    }
}
