
#Installation.
#### Codebase.
`git clone https://github.com/rudamaia/movieDB FOLDERNAME`  
`cd FOLDERNAME`  
`composer install`  
#### Install using Drush or Web interface.
`vendor/drush/drush/drush si standard --db-url=mysql://root@localhost/moviedb`
#### Reset some data in order to import the config in a fresh installed Drupal.
`vendor/drush/drush/drush ev '\Drupal::entityManager()->getStorage("shortcut_set")->load("default")->delete();'`  

`vendor/drush/drush/drush config-set "system.site" uuid 7217f523-e796-4ebb-8d7a-f3e3648964d8 -y`
#### Import Config.
`vendor/drush/drush/drush cim -y`

*You Drupal instalation should be now ready for data fetching.*
#Data Fetching
#### Drupal admin interface. (logged in as Admin)
1. Configure your movieDB api key in 'admin/config/moviedb_api/externalapikey'.
2. Import Movie Genres so they can be referenced in movies later on. Go to **'admin/content/entity-importer/genres' and click 'import'**.
3. Set how many entries you wish to import so it won't take too long. Go to **'admin/config/system/entity-importer/popular_actors/edit'** and input **'Entries to proccess'** to 100, **click Save**.
4. Import Popular Actors and the movies they are known for. Go to **'admin/content/entity-importer/popular_actors'** and **click 'import'**.
5. Reindex Search, run **'vendor/drush/drush/drush cron'** or go to 'admin/reports/status' and click 'Run cron'.

# Comments.
_PROJECT CORE COMPONENTS:_
### Custom module 'moviedb_api' located in 'modules/custom/moviedb_api'.
1. Creates a RESTful client for MovieDB as a service.
2. Implements a custom entity importer for MovieDB.
3. Creates a config form to store the MovieDB api key.
4. The module uses Drupal Hooks for the Movie, Review, Actor and Companies creation proccess.

### Custom Theme 'moviedb' located in 'themes/custom/moviedb/
1. A **subtheme** of **bootstrap_barrio** theme.
2. Uses **SCSS** and custom **Twig templating** files in order to achieve the desired layout.
