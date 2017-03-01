<?php

namespace As3\Modlr\Rest;

/**
 * The REST Request object.
 * Is created/parsed from a core Request object.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RestRequest
{
    /**
     * Request parameter (query string) constants.
     */
    const PARAM_INCLUSIONS = 'include';
    const PARAM_FIELDSETS  = 'fields';
    const PARAM_SORTING    = 'sort';
    const PARAM_PAGINATION = 'page';
    const PARAM_FILTERING  = 'filter';

    /**
     * Filter parameters.
     */
    const FILTER_AUTOCOMPLETE = 'autocomplete';
    const FILTER_AUTOCOMPLETE_KEY = 'key';
    const FILTER_AUTOCOMPLETE_VALUE = 'value';
    const FILTER_QUERY = 'query';
    const FILTER_QUERY_CRITERIA = 'criteria';

    /**
     * The request method, such as GET, POST, PATCH, etc.
     *
     * @var string
     */
    private $requestMethod;

    /**
     * The parsed URL/URI, via PHP's parse_url().
     *
     * @var array
     */
    private $parsedUri = [];

    /**
     * The entity type requested.
     *
     * @var string
     */
    private $entityType;

    /**
     * The entity identifier (id) value, if sent.
     *
     * @var string|null
     */
    private $identifier;

    /**
     * The entity relationship properties, if sent.
     *
     * @var array
     */
    private $relationship = [];

    /**
     * Relationship fields to include with the response.
     * AKA: sideloading the entities of relationships.
     * Either a associative array of relationshipKeys => true to specifically include.
     * Or a single associative key of '*' => true if all should be included.
     *
     * @var array
     */
    private $inclusions = [];

    /**
     * Sorting criteria.
     *
     * @var array
     */
    private $sorting = [];

    /**
     * Fields to only include with the response.
     *
     * @var array
     */
    private $fields = [];

    /**
     * Pagination (limit/skip) criteria.
     *
     * @var array
     */
    private $pagination = [];

    /**
     * Any request filters, such as quering, search, autocomplete, etc.
     * Must ultimately be handled by the Adapter to function.
     *
     * @var array
     */
    private $filters = [];

    /**
     * The request payload, if sent.
     * Used for updating/creating entities.
     *
     * @var RestPayload|null
     */
    private $payload;

    /**
     * The REST configuration.
     *
     * @var RestConfiguration
     */
    private $config;

    /**
     * @var string
     */
    private $uri;

    /**
     * Constructor.
     *
     * @param   RestConfiguration   $config     The REST configuration.
     * @param   string              $method     The request method.
     * @param   string              $uri        The complete URI (URL) of the request, included scheme, host, path, and query string.
     * @param   string|null         $payload    The request payload (body).
     */
    public function __construct(RestConfiguration $config, $method, $uri, $payload = null)
    {
        $this->config = $config;
        $this->uri = $uri;
        $this->requestMethod = strtoupper($method);

        if ($this->config->getRootEndpoint() !== $this->getEndpointPrefix()) {
            $this->config->setRootEndpoint($this->getEndpointPrefix());
        }

        $this->sorting      = $config->getDefaultSorting();
        $this->pagination   = $config->getDefaultPagination();

        $this->parse($uri);
        $this->payload = empty($payload) ? null : new RestPayload($payload);

        // Re-configure the config based on the actually request.
        $this->config->setHost($this->getHost());
        $this->config->setScheme($this->getScheme());
    }

    /**
     * Generates the request URL based on its current object state.
     *
     * @todo    Add support for inclusions and other items.
     * @return  string
     */
    public function getUrl()
    {
        $query = $this->getQueryString();
        return sprintf('%s://%s/%s/%s%s',
            $this->getScheme(),
            trim($this->getHost(), '/'),
            trim($this->getEndpointPrefix(), '/'),
            $this->getEntityType(),
            empty($query) ? '' : sprintf('?%s', $query)
        );
    }

    protected function getEndpointPrefix()
    {
        $path = parse_url($this->uri)['path'];
        return substr(
            $path,
            0,
            strrpos(
                $path,
                $this->config->getRootEndpoint()
            ) + strlen($this->config->getRootEndpoint())
        );
    }

    /**
     * Gets the scheme, such as http or https.
     *
     * @return  string
     */
    public function getScheme()
    {
        return $this->parsedUri['scheme'];
    }

    /**
     * Gets the hostname.
     *
     * @return  string
     */
    public function getHost()
    {
        return $this->parsedUri['host'];
    }

    /**
     * Gets the request method, such as GET, POST, PATCH, etc.
     *
     * @return  string
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Gets the requested entity type.
     *
     * @return  string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Gets the requested entity identifier (id), if sent.
     *
     * @return  string|null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Gets the query string based on the current object properties.
     *
     * @return  string
     */
    public function getQueryString()
    {
        $query = [];
        if (!empty($this->pagination)) {
            $query[self::PARAM_PAGINATION] = $this->pagination;
        }
        if (!empty($this->filters)) {
            $query[self::PARAM_FILTERING] = $this->filters;
        }
        foreach ($this->fields as $modelType => $fields) {
            $query[self::PARAM_FIELDSETS][$modelType] = implode(',', $fields);
        }
        $sort = [];
        foreach ($this->sorting as $key => $direction) {
            $sort[] = (1 === $direction) ? $key : sprintf('-%s', $key);
        }
        if (!empty($sort)) {
            $query[self::PARAM_SORTING] = implode(',', $sort);
        }
        return http_build_query($query);
    }

    /**
     * Determines if an entity identifier (id) was sent with the request.
     *
     * @return  bool
     */
    public function hasIdentifier()
    {
        return null !== $this->getIdentifier();
    }

    /**
     * Determines if this is an entity relationship request.
     *
     * @return  bool
     */
    public function isRelationship()
    {
        return !empty($this->relationship);
    }

    /**
     * Gets the entity relationship request.
     *
     * @return  array
     */
    public function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * Gets the entity relationship field key.
     *
     * @return  string|null
     */
    public function getRelationshipFieldKey()
    {
        if (false === $this->isRelationship()) {
            return null;
        }
        return $this->getRelationship()['field'];
    }

    /**
     * Determines if this is an entity relationship retrieve request.
     *
     * @return  bool
     */
    public function isRelationshipRetrieve()
    {
        if (false === $this->isRelationship()) {
            return false;
        }
        return 'self' === $this->getRelationship()['type'];
    }

    /**
     * Determines if this is an entity relationship modify (create/update/delete) request.
     *
     * @return  bool
     */
    public function isRelationshipModify()
    {
        if (false === $this->isRelationship()) {
            return false;
        }
        return 'related' === $this->getRelationship()['type'];
    }

    /**
     * Determines if this has an autocomplete filter enabled.
     *
     * @return  bool
     */
    public function isAutocomplete()
    {
        if (false === $this->hasFilter(self::FILTER_AUTOCOMPLETE)) {
            return false;
        }
        $autocomplete = $this->getFilter(self::FILTER_AUTOCOMPLETE);
        return isset($autocomplete[self::FILTER_AUTOCOMPLETE_KEY]) && isset($autocomplete[self::FILTER_AUTOCOMPLETE_VALUE]);
    }

    /**
     * Gets the autocomplete attribute key.
     *
     * @return  string|null
     */
    public function getAutocompleteKey()
    {
        if (false === $this->isAutocomplete()) {
            return null;
        }
        return $this->getFilter(self::FILTER_AUTOCOMPLETE)[self::FILTER_AUTOCOMPLETE_KEY];
    }

    /**
     * Gets the autocomplete search value.
     *
     * @return  string|null
     */
    public function getAutocompleteValue()
    {
        if (false === $this->isAutocomplete()) {
            return null;
        }
        return $this->getFilter(self::FILTER_AUTOCOMPLETE)[self::FILTER_AUTOCOMPLETE_VALUE];
    }

    /**
     * Determines if this has the database query filter enabled.
     *
     * @return  bool
     */
    public function isQuery()
    {
        if (false === $this->hasFilter(self::FILTER_QUERY)) {
            return false;
        }
        $query = $this->getFilter(self::FILTER_QUERY);
        return isset($query[self::FILTER_QUERY_CRITERIA]);
    }

    /**
     * Gets the query criteria value.
     *
     * @return  array
     */
    public function getQueryCriteria()
    {
        if (false === $this->isQuery()) {
            return [];
        }

        $queryKey = self::FILTER_QUERY;
        $criteriaKey = self::FILTER_QUERY_CRITERIA;

        $decoded = @json_decode($this->getFilter($queryKey)[$criteriaKey], true);
        if (!is_array($decoded)) {
            $param = sprintf('%s[%s][%s]', self::PARAM_FILTERING, $queryKey, $criteriaKey);
            throw RestException::invalidQueryParam($param, 'Was the value sent as valid JSON?');
        }
        return $decoded;
    }

    /**
     * Determines if specific sideloaded include fields were requested.
     *
     * @return  bool
     */
    public function hasInclusions()
    {
        $value = $this->getInclusions();
        return !empty($value);
    }

    /**
     * Gets specific sideloaded relationship fields to include.
     *
     * @return  array
     */
    public function getInclusions()
    {
        return $this->inclusions;
    }

    /**
     * Determines if a specific return fieldset has been specified.
     *
     * @return  bool
     */
    public function hasFieldset()
    {
        $value = $this->getFieldset();
        return !empty($value);
    }

    /**
     * Gets the return fieldset to use.
     *
     * @return  array
     */
    public function getFieldset()
    {
        return $this->fields;
    }

    /**
     * Determines if the request has specified sorting criteria.
     *
     * @return  bool
     */
    public function hasSorting()
    {
        $value = $this->getSorting();
        return !empty($value);
    }

    /**
     * Gets the sorting criteria.
     *
     * @return  array
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Determines if the request has specified pagination (limit/offset) criteria.
     *
     * @return  bool
     */
    public function hasPagination()
    {
        $value = $this->getPagination();
        return !empty($value);
    }

    /**
     * Gets the pagination (limit/offset) criteria.
     *
     * @return  array
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * Sets the pagination (limit/offset) criteria.
     *
     * @param   int     $offset
     * @param   int     $limit
     * @return  self
     */
    public function setPagination($offset, $limit)
    {
        $this->pagination['offset'] = (Integer) $offset;
        $this->pagination['limit'] = (Integer) $limit;
        return $this;
    }

    /**
     * Determines if the request has any filtering criteria.
     *
     * @return  bool
     */
    public function hasFilters()
    {
        return !empty($this->filters);
    }

    /**
     * Determines if a specific filter exists, by key
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasFilter($key)
    {
        return null !== $this->getFilter($key);
    }

    /**
     * Gets a specific filter, by key.
     *
     * @param   string  $key
     * @return  mixed|null
     */
    public function getFilter($key)
    {
        if (!isset($this->filters[$key])) {
            return null;
        }
        return $this->filters[$key];
    }

    /**
     * Gets the request payload.
     *
     * @return  RestPayload|null
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Determines if a request payload is present.
     *
     * @return  bool
     */
    public function hasPayload()
    {
        return $this->getPayload() instanceof RestPayload;
    }

    /**
     * Parses the incoming request URI/URL and sets the appropriate properties on this RestRequest object.
     *
     * @param   string  $uri
     * @return  self
     * @throws  RestException
     */
    private function parse($uri)
    {
        $this->parsedUri = parse_url($uri);

        if (false === strstr($this->parsedUri['path'], $this->config->getRootEndpoint())) {
            throw RestException::invalidEndpoint($this->parsedUri['path']);
        }

        $this->parsedUri['path'] = str_replace($this->config->getRootEndpoint(), '', $this->parsedUri['path']);
        $this->parsePath($this->parsedUri['path']);

        $this->parsedUri['query'] = isset($this->parsedUri['query']) ? $this->parsedUri['query'] : '';
        $this->parseQueryString($this->parsedUri['query']);

        return $this;
    }

    /**
     * Parses the incoming request path and sets appropriate properties on this RestRequest object.
     *
     * @param   string  $path
     * @return  self
     * @throws  RestException
     */
    private function parsePath($path)
    {
        $parts = explode('/', trim($path, '/'));
        for ($i = 0; $i < 1; $i++) {
            // All paths must contain /{workspace_entityType}
            if (false === $this->issetNotEmpty($i, $parts)) {
                throw RestException::invalidEndpoint($path);
            }
        }
        $this->extractEntityType($parts);
        $this->extractIdentifier($parts);
        $this->extractRelationship($parts);
        return $this;
    }

    /**
     * Extracts the entity type from an array of path parts.
     *
     * @param   array   $parts
     * @return  self
     */
    private function extractEntityType(array $parts)
    {
        $this->entityType = $parts[0];
        return $this;
    }

    /**
     * Extracts the entity identifier (id) from an array of path parts.
     *
     * @param   array   $parts
     * @return  self
     */
    private function extractIdentifier(array $parts)
    {
        if (isset($parts[1])) {
            $this->identifier = $parts[1];
        }
        return $this;
    }

    /**
     * Extracts the entity relationship properties from an array of path parts.
     *
     * @param   array   $parts
     * @return  self
     */
    private function extractRelationship(array $parts)
    {
        if (isset($parts[2])) {
            if ('relationships' === $parts[2]) {
                if (!isset($parts[3])) {
                    throw RestException::invalidRelationshipEndpoint($this->parsedUri['path']);
                }
                $this->relationship = [
                    'type'  => 'self',
                    'field' => $parts[3],
                ];
            } else {
                $this->relationship = [
                    'type'  => 'related',
                    'field' => $parts[2],
                ];
            }
        }
        return $this;
    }

    /**
     * Parses the incoming request query string and sets appropriate properties on this RestRequest object.
     *
     * @param   string  $queryString
     * @return  self
     * @throws  RestException
     */
    private function parseQueryString($queryString)
    {
        parse_str($queryString, $parsed);

        $supported = $this->getSupportedParams();
        foreach ($parsed as $param => $value) {
            if (!isset($supported[$param])) {
                throw RestException::unsupportedQueryParam($param, array_keys($supported));
            }
        }

        $this->extractInclusions($parsed);
        $this->extractSorting($parsed);
        $this->extractFields($parsed);
        $this->extractPagination($parsed);
        $this->extractFilters($parsed);
        return $this;
    }

    /**
     * Extracts relationship inclusions from an array of query params.
     *
     * @param   array   $params
     * @return  self
     */
    private function extractInclusions(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_INCLUSIONS, $params)) {
            if (true === $this->config->includeAllByDefault()) {
                $this->inclusions = ['*' => true];
            }
            return $this;
        }
        $inclusions = explode(',', $params[self::PARAM_INCLUSIONS]);
        foreach ($inclusions as $inclusion) {
            if (false !== stristr($inclusion, '.')) {
                throw RestException::invalidParamValue(self::PARAM_INCLUSIONS, sprintf('Inclusion via a relationship path, e.g. "%s" is currently not supported.', $inclusion));
            }
            $this->inclusions[$inclusion] = true;
        }
        return $this;
    }

    /**
     * Extracts sorting criteria from an array of query params.
     *
     * @param   array   $params
     * @return  self
     */
    private function extractSorting(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_SORTING, $params)) {
            return $this;
        }
        $sort = explode(',', $params[self::PARAM_SORTING]);
        $this->sorting = [];
        foreach ($sort as $field) {
            $direction = 1;
            if (0 === strpos($field, '-')) {
                $direction = -1;
                $field = str_replace('-', '', $field);
            }
            $this->sorting[$field] = $direction;
        }
        return $this;
    }

    /**
     * Extracts fields to return from an array of query params.
     *
     * @param   array   $params
     * @return  self
     */
    private function extractFields(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_FIELDSETS, $params)) {
            return $this;
        }
        $fields = $params[self::PARAM_FIELDSETS];
        if (!is_array($fields)) {
            throw RestException::invalidQueryParam(self::PARAM_FIELDSETS, 'The field parameter must be an array of entity type keys to fields.');
        }
        foreach ($fields as $entityType => $string) {
            $this->fields[$entityType] = explode(',', $string);
        }
        return $this;
    }

    /**
     * Extracts pagination criteria from an array of query params.
     *
     * @param   array   $params
     * @return  self
     */
    private function extractPagination(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_PAGINATION, $params)) {
            return $this;
        }
        $page = $params[self::PARAM_PAGINATION];
        if (!is_array($page) || !isset($page['limit'])) {
            throw RestException::invalidQueryParam(self::PARAM_PAGINATION, 'The page parameter must be an array containing at least a limit.');
        }
        $this->pagination = [
            'offset'    => isset($page['offset']) ? (Integer) $page['offset'] : 0,
            'limit'     => (Integer) $page['limit'],
        ];
        return $this;
    }

    /**
     * Extracts filtering criteria from an array of query params.
     *
     * @param   array   $params
     * @return  self
     */
    private function extractFilters(array $params)
    {
        if (false === $this->issetNotEmpty(self::PARAM_FILTERING, $params)) {
            return $this;
        }
        $filters = $params[self::PARAM_FILTERING];
        if (!is_array($filters)) {
            throw RestException::invalidQueryParam(self::PARAM_FILTERING, 'The filter parameter must be an array keyed by filter name and value.');
        }
        foreach ($filters as $key => $value) {
            $this->filters[$key] = $value;
        }
        return $this;
    }

    /**
     * Gets query string parameters that this request supports.
     *
     * @return  array
     */
    public function getSupportedParams()
    {
        return [
            self::PARAM_INCLUSIONS  => true,
            self::PARAM_FIELDSETS   => true,
            self::PARAM_SORTING     => true,
            self::PARAM_PAGINATION  => true,
            self::PARAM_FILTERING   => true,
        ];
    }

    /**
     * Helper that determines if a key and value is set and is not empty.
     *
     * @param   string  $key
     * @param   mixed   $value
     * @return  bool
     */
    private function issetNotEmpty($key, $value)
    {
        return isset($value[$key]) && !empty($value[$key]);
    }
}
