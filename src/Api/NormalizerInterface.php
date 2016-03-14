<?php

namespace As3\Modlr\Api;

use As3\Modlr\Rest\RestPayload;

/**
 * Interface for normalizing rest payloads in the implementing format.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface NormalizerInterface
{
    /**
     * Normalizes a RestPayload into an array that can be applied to a model.
     *
     * @param   RestPayload         $payload    The incoming payload.
     * @param   AdapterInterface    $adapter    The adapter.
     * @return  array
     */
    public function normalize(RestPayload $payload, AdapterInterface $adapter);
}
