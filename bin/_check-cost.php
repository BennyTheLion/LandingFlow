<?php
/** Temporary — reports which paid integrations are reachable. Delete after use. */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
require_once CONFIG_PATH . '/loader.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/Autoloader.php';
\App\Core\Autoloader::register();

printf("AI_ENABLED              %s\n", defined('AI_ENABLED') ? var_export(AI_ENABLED, true) : 'NOT DEFINED');
printf("AI_API_KEY              %s\n", defined('AI_API_KEY') ? (AI_API_KEY === '' ? "'' empty" : 'SET') : 'NOT DEFINED');
printf("GOOGLE_PLACES_API_KEY   %s\n", defined('GOOGLE_PLACES_API_KEY') ? (GOOGLE_PLACES_API_KEY === '' ? "'' empty" : 'SET') : 'NOT DEFINED');
printf("PAGESPEED_API_KEY       %s\n", defined('PAGESPEED_API_KEY') ? (PAGESPEED_API_KEY === '' ? "'' empty" : 'SET') : 'NOT DEFINED');
echo str_repeat('-', 52) . "\n";
printf("LLM callable            %s\n", (new \App\Services\OpenAiService())->isAvailable() ? 'YES -> COSTS MONEY' : 'no');
printf("Google Places callable  %s\n", (new \App\LeadEngine\GooglePlacesClient())->isAvailable() ? 'YES -> COSTS MONEY' : 'no');
printf("PageSpeed callable      %s\n", (new \App\LeadEngine\PageSpeedClient())->isAvailable() ? 'yes (free quota)' : 'no');
printf("pipeline_enabled        %s\n", \App\LeadEngine\LeadEngineConfig::bool('pipeline_enabled') ? 'YES' : 'no');
printf("sending_halted          %s\n", \App\LeadEngine\LeadEngineConfig::bool('sending_halted') ? 'yes (frozen)' : 'NO');
