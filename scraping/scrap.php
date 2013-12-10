<?php
namespace Scraping\Scrap;
// scraping algorithm - DEVELOP INTENTIONALLY TO DONT BE OBJECT ORIENTED
// as computer science is all about analyzing trade-offs, object orientation in scripts like this offer no advantage;
// actually, it is a bad idea. There are no entities here to have attributes and be treated as objects, and functions here
// are mostly procedures. It also performs worse, and this script will not become an entire project; its just scrapping.
// so, stick with it and dont have the dummy idea to make classes out of this.

// Run cURL proccesses paralelly

function buildUrl($id, $type)
{
	settype($id, 'int');
	$type = strtolower($type);

	switch ($type) {
		case \Scraping\Config\SCHEDULE_FLAG:
			return \Scraping\Config\ROUTES_SCHEDULE_URL . $id;
			break;
		case \Scraping\Config\PRICE_FLAG:
			return \Scraping\Config\ROUTES_PRICE_URL . $id;
			break;
		default:
			return false;
			break;
	}
}

function createUniqueHandler($id, $type)
{
	settype($id, 'int');

	$url = buildUrl($id, $type);

	if (!$url) {
		return false;
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	return $ch;
}

function createMultipleHandler($array_of_handlers)
{
	if (!is_array($array_of_handlers) || $array_of_handlers === array()) {
		return false;
	}

	$mh = curl_multi_init();

	for ($i = 0; $i < \Scraping\Config\NUMBER_OF_PROCESSES; $i++) {
		curl_multi_add_handle($mh, $array_of_handlers[$i]);
	}

	return $mh;
}

function createArrayOfHandlers($begin, $type)
{
	settype($begin, 'int');
	$array_of_handlers = array();

	for ($i = 0; $i < \Scraping\Config\NUMBER_OF_PROCESSES; $i++) {
		$array_of_handlers[$i] = createUniqueHandler($begin + $i, $type);
	}

	return $array_of_handlers;
}

function proccessParallelCalls($mh)
{
	$active = null;

	do {
    	$mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);

	while ($active && $mrc == CURLM_OK) {
    	if (curl_multi_select($mh) != -1) {
        	do {
            	$mrc = curl_multi_exec($mh, $active);
        	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
    	}
	}
}

function parseResults($array_of_handlers, $type, $begin, $pdo_object)
{
	// $begin + $i = ID OF THE ROUTE ON ANTT.GOV.BR WEBSITE
	for ($i = 0; $i < \Scraping\Config\NUMBER_OF_PROCESSES; $i++) {
		\Scraping\Parse\parseAndInsert(curl_multi_getcontent($array_of_handlers[$i]), $type, $begin + $i, $pdo_object);
	}	
}

function closeHandlers($mh, $array_of_handlers)
{
	for ($i = 0; $i < \Scraping\Config\NUMBER_OF_PROCESSES; $i++) {
		curl_multi_remove_handle($mh, $array_of_handlers[$i]);
		curl_close($array_of_handlers[$i]);
	}

	curl_multi_close($mh);
}


function fetchAndInsert($array_of_handlers, $type, $begin, $pdo_object)
{
	$mh = createMultipleHandler($array_of_handlers);

	proccessParallelCalls($mh);	
	parseResults($array_of_handlers, $type, $begin, $pdo_object);
	closeHandlers($mh, $array_of_handlers);

	return true;
}

function runSchedule($pdo_object, $begin = 0)
{
	while ($begin <= \Scraping\Config\MAX_NUMBER_SCHEDULE_ROUTES) {
		$array_of_handlers = createArrayOfHandlers($begin, \Scraping\Config\SCHEDULE_FLAG);
		$result = fetchAndInsert($array_of_handlers, \Scraping\Config\SCHEDULE_FLAG, $begin, $pdo_object);

		$begin += \Scraping\Config\NUMBER_OF_PROCESSES;
	}
}

function runPrice($pdo_object, $begin = 0)
{
	while ($begin <= \Scraping\Config\MAX_NUMBER_PRICE_ROUTES) {
		$array_of_handlers = createArrayOfHandlers($begin, \Scraping\Config\PRICE_FLAG);
		fetchAndInsert($array_of_handlers, \Scraping\Config\PRICE_FLAG, $begin, $pdo_object);

		$begin += \Scraping\Config\NUMBER_OF_PROCESSES;	
	}
}