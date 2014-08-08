<?php
header('Content-type: text/html; charset=utf-8');

include('config.php');
include('parse.php');
include('scrap.php');

$dsn = \Scraping\Config\DATABASE_ADAPTER . ':dbname=' . \Scraping\Config\DATABASE_NAME . ';host=' . \Scraping\Config\DATABASE_HOST;
$pdo_object = new PDO($dsn, \Scraping\Config\DATABASE_USERNAME, \Scraping\Config\DATABASE_PASSWORD, array(PDO::ATTR_PERSISTENT => true));

\Scraping\Scrap\runPrice($pdo_object);

echo 'Schedule scrapping done with success.';

$pdo_object = null;