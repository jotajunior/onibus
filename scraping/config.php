<?php
namespace Scraping\Config;

/*****************************************************INI CONFIGURATION*************************************************************************/
set_time_limit(0);
error_reporting(E_ALL ^ E_WARNING);

/******************************************************INTERNAL CONFIGURATION*******************************************************************/

// Flags used to determine whether parsing/fetching procedure is related to website used to scrap prices and routes
define('Scraping\Config\SCHEDULE_FLAG', 'schedule');
define('Scraping\Config\PRICE_FLAG', 'price');

// Flags for internal syntax used to represent routes and their schedules.
// For example: two routes: 
// one starting at 09:00, available on Monday and Wednesday, from January to March;
// the other starting at 13:45, available from Monday to Sunday, in June and December:
// 09:00|1,3|1,2,3*13:45|1,2,3,4,5,6,7|6,12
// The ROUTE_SCHEDULE_SEPARE_INTERNAL_FIELDS (| by default) separe the fields: first, the hour; second, days of the week available; last, 
// months of the year available.
// Internally, the days and months are separed with ROUTE_SCHEDULE_SEPARE_FIELDS_VALUES (, by default);
// And the routes are separed with SCHEDULE_SEPARE_ROUTES (* by default).
define('Scraping\Config\ROUTE_SCHEDULE_SEPARE_INTERNAL_FIELDS', '|');
define('Scraping\Config\ROUTE_SCHEDULE_SEPARE_FIELDS_VALUES', ',');
define('Scraping\Config\SCHEDULE_SEPARE_ROUTES', '*');


/*****************************************************CONFIGURATION RELATED TO ANTT.GOV.BR***************************************************/
define('Scraping\Config\ROUTES_SCHEDULE_URL', 'http://www.antt.gov.br/html/objects/linhas/_carrega_horarios.php?linha=');
define('Scraping\Config\ROUTES_PRICE_URL', 'http://www.antt.gov.br/html/objects/linhas/_carrega_tarifa.php?linha=');
define('Scraping\Config\MAX_NUMBER_SCHEDULE_ROUTES', 10000);
define('Scraping\Config\MAX_NUMBER_PRICE_ROUTES', 10000);


/*****************************************************YOUR CONFIGURATION - YOU CAN CHANGE 'DELIBERATELY'***************************************/
// Parallel Processing
define('Scraping\Config\NUMBER_OF_PROCESSES', 5);
define('Scraping\Config\PRICE_STEP_ON_QUERY', 50);

// Database Configuration
define('Scraping\Config\DATABASE_ADAPTER', 'mysql');
define('Scraping\Config\DATABASE_HOST', 'localhost');
define('Scraping\Config\DATABASE_USERNAME', 'root');
define('Scraping\Config\DATABASE_PASSWORD', 'Fropme-2012');
define('Scraping\Config\DATABASE_NAME', 'onibus');
define('Scraping\Config\DATABASE_ROUTES_SCHEDULE_TABLE', 'rotas');
define('Scraping\Config\DATABASE_ROUTES_PRICE_TABLE', 'subrotas');