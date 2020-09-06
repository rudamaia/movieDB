<?php

namespace Drupal\moviedb_api;

use GuzzleHttp\Client;

/**
 * Generic RESTful client for MovieDB API.
 */
class MovieDBApiClient
{

    /**
     * Base URI for requests.
     *
     * @var string
     */
    protected $baseUri = 'https://api.themoviedb.org/3/';

    /**
     * HTTP Client for making API requests.
     *
     * @var GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Drupal's settings manager.
     */
    protected $settings;

    /**
     * MovieDBAPI constructor.
     *
     * @param \GuzzleHttp\Client $http_client
     *   The http client service.
     */
    public function __construct(
        Client $http_client
    ) {
        $this->settings = \Drupal::config('moviedb_api.externalapikey');
        $this->httpClient = $http_client;
    }

    /**
     * Generic request method.
     */
    public function request($method, $endpoint, $query_params = [])
    {
        $api_key = $this->settings->get('your_external_api_key');

        $query_params = ["api_key" => $api_key] + $query_params;
        // dump($query_params);
        $params = [
            'query' => $query_params,
        ];

        $result = $this->httpClient->{$method}(
            $this->baseUri . $endpoint,
            $params
        );

        $json_response = json_decode((string) $result->getBody(), true);
        // dump($json_response);
        return $json_response;
    }

    /**
     * Get request method.
     */
    public function get($endpoint, $params = [])
    {
        return $this->request('GET', $endpoint, $params);
    }

}
