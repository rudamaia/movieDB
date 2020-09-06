<?php

namespace Drupal\moviedb_api\Plugin\migrate\process;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessTrait;
use Drupal\migrate\ProcessPluginBase;

/**
 * Define entity import migration lookup.
 *
 * @MigrateProcessPlugin(
 *   id = "moviedb_movies_to_string",
 *   label = @Translation("MovieDB Movies to String"),
 *   handle_multiples = TRUE
 * )
 */
class MovieDBMoviesToString extends ProcessPluginBase implements EntityImportProcessInterface
{

    use EntityImportProcessTrait;

    /**
     * {@inheritdoc}
     */
    public function defaultConfigurations()
    {
        return ['method' => 'moviesToString'];
    }

    /**
     * Callback method.
     */
    public function moviesToString(array $value)
    {
        if ($value) {
            // Store actor movies.
            foreach ($value as $value) {
                $values[] = json_encode($value);
            }
        }

        return $values ? $values : '';
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['description'] = [
            '#type' => '#markup',
            '#markup' => $this->t('There are no configuration options for this plugin.'),
        ];
        return $form;
    }

}
