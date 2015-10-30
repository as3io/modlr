<?php

namespace Actinoids\Modlr\RestOdm\StoreNormalizer;

use Actinoids\Modlr\RestOdm\StoreAdapter\JsonApiAdapter;
use Actinoids\Modlr\RestOdm\Rest\RestPayload;
use Actinoids\Modlr\RestOdm\Struct;

/**
 * Interface for normalizing rest payloads in the implementing format.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface NormalizerInterface
{
    /**
     * Normalizes a RestPayload into a Struct\Resource.
     *
     * @param   RestPayload         $payload    The incoming payload.
     * @param   AdapterInterface    $adapter    The adapter.
     * @return  Struct\Resource
     */
    public function normalize(RestPayload $payload, JsonApiAdapter $adapter);
}
