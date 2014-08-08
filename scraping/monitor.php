<?php
include('config.php');
$dsn = \Scraping\Config\DATABASE_ADAPTER . ':dbname=' . \Scraping\Config\DATABASE_NAME . ';host=' . \Scraping\Config\DATABASE_HOST;
$pdo_object = new PDO($dsn, \Scraping\Config\DATABASE_USERNAME, \Scraping\Config\DATABASE_PASSWORD);

foreach ($pdo_object->query('SELECT COUNT(*) AS rota_contador FROM rotas') as $row) {
	echo $row['rota_contador'];
}

$pdo_object = null;