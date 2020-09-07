<?php

namespace Drupal\moviedb_api\Plugin\migrate\source;

use AppendIterator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Plugin\migrate\source\EntityImportSourceLimitIteratorBase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Row;
use Drupal\moviedb_api\MovieDBApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a flexible, generic import source for the MobieDB API.
 *
 * @MigrateSource(
 *   id = "entity_import_moviedb",
 *   label = @Translation("MovieDB API"),
 * )
 */
class EntityImportSourceMovieDB extends EntityImportSourceLimitIteratorBase implements RequirementsInterface
{

    /**
     * Total entries from MovieDB.
     *
     * @var int
     */
    protected $totalEntries = 0;

    /**
     * Current page number for API calls.
     *
     * @var int
     */
    protected $currentPage = 1;

    /**
     * The MovieDB API service.
     *
     * @var \Drupal\moviedb_api\MovieDBApiClient
     */
    protected $moviedbApiService;

    /**
     * {@inheritdoc}
     */
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition,
        MigrationInterface $migration = null
    ) {
        $instance = parent::create(
            $container,
            $configuration,
            $plugin_id,
            $plugin_definition,
            $migration,
            $container->get('entity_type.manager')
        );
        $instance->setMovieDBApiService($container->get('moviedb_api.client'));
        return $instance;
    }

    /**
     * Setter for $moviedbAPIService.
     */
    public function setMovieDBApiService(MovieDBApiClient $moviedb_api_service)
    {
        $this->moviedbApiService = $moviedb_api_service;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        $config = $this->getConfiguration();
        return !empty($config['endpoint']);
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        $config = $this->getConfiguration();
        if (!isset($config['endpoint'])) {
            throw new RequirementsException(
                'Missing endpoint.',
                ['endpoint']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildImportForm(array $form, FormStateInterface $form_state)
    {

        $config = $this->getConfiguration();

        // Making an API request will set up total item counts.
        $this->apiRequest();
        $values = [
            $this->t('Ready to import %count items from the endpoint %endpoint.', ['%count' => $this->totalEntries, '%endpoint' => $config['endpoint']]),
        ];

        // The form just shows overview data about the items to import.
        $form['moviedb_importer'] = [
            '#prefix' => '<h2>' . $this->t('MovieDB importer') . '</h2><pre>',
            '#suffix' => '</pre>',
            '#markup' => implode('<br/>', $values),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->getConfiguration();

        $form['endpoint'] = [
            '#type' => 'textfield',
            '#title' => $this->t('MovieDB Endpoint'),
            '#default_value' => $config['endpoint'],
        ];

        $description = [
            $this->t('JSON encoded parameters to include in request (i.e. [{"is_active": "true"}]).'),
            $this->t('You can specify dynamic dates with [date:today], [date:first day of last month], etc.'),
        ];

        $form['params'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Query Parameters'),
            '#default_value' => $config['params'],
            '#description' => implode('<br/>', $description),
        ];
        $form['throttle'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Throttle'),
            '#default_value' => $config['throttle'],
            '#description' => $this->t('Limit the number of API items processed per request.'),
        ];
        $form['total_entries'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Entries to proccess'),
            '#default_value' => $config['total_entries'],
        ];

        return $form;
    }

    /**
     * Initialize the migrate source iterator with results from an API request.
     *
     * The request is paginated. We determine the current page based on the
     * limit count and offset. The limit count is stored in configuration.
     * The limit offset is set by the batch processor and increments for
     * each batch (see \Drupal\entity_import\Form\EntityImporterBatchProcess).
     */
    public function initializeIterator()
    {
        $this->currentPage = ($this->getLimitCount() + $this->getLimitOffset()) / $this->getLimitCount();
        $results = $this->apiRequest($this->currentPage);
        $this->createIterator($results);

        return $this->iterator;
    }

    /**
     * Return the limit count from configuration.
     */
    public function getLimitCount()
    {
        $config = $this->getConfiguration();
        if (!empty($config['throttle'])) {
            return $config['throttle'];
        }
        return parent::getLimitCount();
    }

    /**
     * Make an API request.
     */
    public function apiRequest($page = 1)
    {

        $config = $this->getConfiguration();

        // Set up API request parameters.
        $params = json_decode($this->replaceParamTokens($config['params']), true);
        $params['page'] = $page;

        $response = $this->moviedbApiService->get($config['endpoint'], $params);
        // Actors.
        if (isset($response['total_results'])) {
            $this->totalEntries = $config['total_entries'] > 0 ? $config['total_entries'] : $response['total_results'];
            return $response['results'];
        }
        // Genres.
        $this->totalEntries = $config['total_entries'] > 0 ? $config['total_entries'] : count($response['genres']);
        return $response['genres'];

    }

    /**
     * Advance to the next row in the iterator, with pagination.
     *
     * Typically next() will fail when it reaches the end of an iterator.
     * When called outside the context of a batch process (for example, Drush with
     * Migrate Tools) we need to advance the page and attempt another API request.
     * Otherwise the source would always be limited to a single page.
     */
    public function next()
    {
        // If not called from inside a batch process, get the next page of data.
        if (!$this->isBatch && !$this->getIterator()->valid()) {
            $this->currentPage++;
            $results = $this->apiRequest($this->currentPage);

            if (count($results)) {
                $this->createIterator($results);
            }
        }
        return parent::next();
    }

    /**
     * Create the iterator and populate with source data.
     *
     * @param array $source_data
     *   The source data from an API request.
     */
    protected function createIterator(array $source_data)
    {
        $iterator = new AppendIterator();
        $iterator->append(new \ArrayIterator($source_data));
        $this->iterator = $iterator;
        return $this->iterator;
    }

    /**
     * Return the total number of items from MovieDB API.
     */
    protected function doCount()
    {
        $this->apiRequest();
        return $this->totalEntries;
    }

    /**
     * Replace tokens in the parameter string.
     */
    private function replaceParamTokens($params)
    {
        // Parse dates using strtotime() syntax.
        preg_match_all('/\[date:([0-9\-\w\s]+)\]/', $params, $matches);
        foreach ($matches[0] as $index => $match) {
            $date = date('c', strtotime($matches[1][$index]));
            $params = str_replace($match, $date, $params);
        }
        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return json_encode(iterator_to_array($this->iterator));
    }

    /**
     * {@inheritdoc}
     *
     * Unused in this plugin, but required by interface.
     */
    public function limitedIterator()
    {
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultConfiguration()
    {
        return [
            'endpoint' => '',
            'params' => '',
            'throttle' => 10,
            'total_entries' => '',
        ] + parent::defaultConfiguration();
    }

}
