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
	$array_of_handlers = array();

	if (!is_array($begin)) {
		settype($begin, 'int');

		for ($i = 0; $i < \Scraping\Config\NUMBER_OF_PROCESSES; $i++) {
			$array_of_handlers[$i] = createUniqueHandler($begin + $i, $type);
		}
	} else {
		$i = 0;
		foreach ($begin as $id) {
			$array_of_handlers[$i++] = createUniqueHandler($id, $type);
		}
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	echo PHP_EOL;
	print_r($array_of_handlers);
	echo PHP_EOL, "Array of handlers created succesfully", PHP_EOL;
	return $array_of_handlers;
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
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

function parseResults($array_of_handlers, $type, $begin, $pdo_object, $ids_array = NULL)
{
	// $begin + $i = ID OF THE ROUTE ON ANTT.GOV.BR WEBSITE
	for ($i = 0; $i < \Scraping\Config\NUMBER_OF_PROCESSES; $i++) {
		switch ($type) {
			case \Scraping\Config\SCHEDULE_FLAG:
				$result = curl_multi_getcontent($array_of_handlers[$i]);
				$id = $begin + $i;
				break;
			case \Scraping\Config\PRICE_FLAG:
				$result = $array_of_handlers[$i];
				$id = $ids_array[$i];
				break;
			default:
				continue;
				break;
		}

		\Scraping\Parse\parseAndInsert($result, $type, $id, $pdo_object);
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


function fetchAndInsert($array_of_handlers, $type, $begin, $pdo_object, $ids_array = NULL)
{
	switch ($type) {
		case \Scraping\Config\SCHEDULE_FLAG:
			///////////////////////////////////////////////////////////////////////////////////////////
			///////////////////////////////////////////////////////////////////////////////////////////
			///////////////////////////////////////////////////////////////////////////////////////////
			echo "SELECTED TYPE SCHEDULE", PHP_EOL;
			$mh = createMultipleHandler($array_of_handlers);
			var_dump($mh); echo PHP_EOL;
			proccessParallelCalls($mh);	

			parseResults($array_of_handlers, $type, $begin, $pdo_object);
			closeHandlers($mh, $array_of_handlers);
			break;
		case \Scraping\Config\PRICE_FLAG:
			$results = array();
			foreach ($array_of_handlers as &$handler) {
				$results[] = curl_exec($handler);
				curl_close($handler);
			}
			parseResults($results, $type, $begin, $pdo_object, $ids_array);
			break;
	}
	return true;
}

function runSchedule($pdo_object, $begin = 0)
{
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	echo 1;
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////
	while ($begin <= \Scraping\Config\MAX_NUMBER_SCHEDULE_ROUTES) {
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		echo PHP_EOL, $begin;
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////
		$array_of_handlers = createArrayOfHandlers($begin, \Scraping\Config\SCHEDULE_FLAG);
		$result = fetchAndInsert($array_of_handlers, \Scraping\Config\SCHEDULE_FLAG, $begin, $pdo_object);

		$begin += \Scraping\Config\NUMBER_OF_PROCESSES;
	}
}

function runPrice($pdo_object, $begin = 0)
{
	$max_routes = \Scraping\Parse\getNumberOfScheduleRoutes($pdo_object);

	while ($begin <= $max_routes) {
		$ids = \Scraping\Parse\getScheduleIds($pdo_object, $begin);
		$array_of_handlers = createArrayOfHandlers($ids, \Scraping\Config\PRICE_FLAG);
		fetchAndInsert($array_of_handlers, \Scraping\Config\PRICE_FLAG, $begin, $pdo_object, $ids);

		$begin += \Scraping\Config\NUMBER_OF_PROCESSES;
	}
}