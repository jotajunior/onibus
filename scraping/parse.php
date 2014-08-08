<?php
namespace Scraping\Parse;

function stripEmptyChars($string)
{
	return preg_replace("/\s+/", "", $string);
}

function replaceAllWithSingleSpace($string)
{
    return preg_replace("/\s+/", " ", $string);   
}

function parseFirstRoundSchedule(&$fields_gotten, &$xpath)
{
    $query = '//div[@class="cabecalho"]/div[@class="linha"]/div[@class="campo"]/label';
	$entries = $xpath->query($query);

	foreach ($entries as $entry) {
		if (stripEmptyChars($entry->previousSibling->nodeValue) == '' || stripEmptyChars($entry->nodeValue) == '') {
			continue;
		}

    	switch ($entry->previousSibling->nodeValue) {
    		case 'Descrição:':
    			$fields_gotten['descricao'] = $entry->nodeValue;
    			break;
    		case 'Razão Social:':
    			$fields_gotten['razao_social'] = $entry->nodeValue;
    			break;
    		case 'Atualização:':
    			$fields_gotten['atualizacao'] = $entry->nodeValue;
    			break;
    		case 'Número:':
    			$fields_gotten['numero'] = $entry->nodeValue;
    			break;
    	}
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    var_dump($fields_gotten);
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}

function parseSecondRoundSchedule(&$fields_gotten, &$xpath)
{
    $query = '//div[@class="cabecalho"]/div[@class="linha"]/div[@class="campoL"]/label';
	$entries = $xpath->query($query);

	foreach ($entries as $entry) {
		if (stripEmptyChars($entry->previousSibling->nodeValue) == '' || stripEmptyChars($entry->nodeValue) == '') {
			continue;
		}

    	switch ($entry->previousSibling->nodeValue) {
    		case 'Prefixo:':
    			$fields_gotten['prefixo'] = $entry->nodeValue;
    			break;
    		case 'Empresa:':
    			$fields_gotten['empresa'] = $entry->nodeValue;
    			break;
    		case 'Documento:':
    			$fields_gotten['documento'] = $entry->nodeValue;
    			break;
    		case 'Solicitação:':
    			$fields_gotten['solicitacao'] = $entry->nodeValue;
    			break;
    		case 'Referência:':
    			$fields_gotten['referencia'] = $entry->nodeValue;
    			break;
    	}
    }	
}

function parseThirdRoundSchedule(&$fields_gotten, &$xpath)
{
    $query = '/html/body/div[@id="page"]/div[@class="content"]/div[@class="cabecalho"]/div[@class="linha"]/label';
    $entries = $xpath->query($query);

    foreach ($entries as $entry) {
        if (stripEmptyChars($entry->nodeValue) == '') {
            continue;
        }

        switch ($entry->nodeValue) {
            case 'Serviço:':
                $fields_gotten['servico'] = $entry->nextSibling->nextSibling->nodeValue;
                break;
            case 'Situação do Serviço:':
                $fields_gotten['situacao'] = $entry->nextSibling->nextSibling->nodeValue;
                break;
            case 'Tipo de Veículo:':
                $fields_gotten['tipo'] = $entry->nextSibling->nodeValue;
                break;
            case 'Observação:':
                $fields_gotten['observacao'] = $entry->nextSibling->nodeValue;
                break;
        }
    }
}

function parseGeneralInformationSchedule(&$xpath)
{
    $fields_gotten = array();
    parseFirstRoundSchedule($fields_gotten, $xpath);

    if (count($fields_gotten) == 1) {
        return false;
    }

    parseSecondRoundSchedule($fields_gotten, $xpath);
    parseThirdRoundSchedule($fields_gotten, $xpath);

    return $fields_gotten;
}

function parseCitySchedule(&$xpath, $choice)
{
    settype($choice, 'int');

    $query = '//div[@class="grid"]/table[' . $choice . ']/tr[2]/th';
    $entries = $xpath->query($query);

    foreach ($entries as $entry) {
        $value = replaceAllWithSingleSpace($entry->nodeValue);
        return trim(str_replace('Saída(s) de: ', '', $value));
    }   
}

function parseStartCitySchedule(&$xpath)
{
    return parseCitySchedule($xpath, 1);
}

function parseDestinyCitySchedule(&$xpath)
{
    return parseCitySchedule($xpath, 2);
}

function parseCityHoursSchedule(&$xpath, $choice)
{
    settype($choice, 'int');

    $query = '//div[@class="grid"]/table[' . $choice . ']/tr[position()>3]';
    $entries = $xpath->query($query);
    $hours = array();

    foreach ($entries as $entry) {
        $hours[] = explode("<br/>", stripEmptyChars(nl2br($entry->nodeValue)));
    }

    $str_result = '';

    foreach ($hours as $hour) {
        if (!isset($hour[1])) {
            continue;
        }

        $str_result .= $hour[1] . \Scraping\Config\ROUTE_SCHEDULE_SEPARE_INTERNAL_FIELDS;
        
        for ($i = 2; $i <= 8; $i++) {
            if ($hour[$i] == 'X') {
                $str_result .= ($i - 1) . \Scraping\Config\ROUTE_SCHEDULE_SEPARE_FIELDS_VALUES;
            }
        }

        $str_result = substr($str_result, 0, -1);

        $str_result .= \Scraping\Config\ROUTE_SCHEDULE_SEPARE_INTERNAL_FIELDS;

        for ($i = 10; $i <= 21; $i++) {
            if ($hour[$i] == 'X') {
                $str_result .= ($i - 9) . \Scraping\Config\ROUTE_SCHEDULE_SEPARE_FIELDS_VALUES;
            }
        }

        $str_result = substr($str_result, 0, -1);

        $str_result .= \Scraping\Config\SCHEDULE_SEPARE_ROUTES;
    }

    $str_result = substr($str_result, 0, -1);
    return $str_result;
}

function parseStartCityHoursSchedule(&$xpath)
{
    return parseCityHoursSchedule($xpath, 1);
}

function parseDestinyCityHoursSchedule(&$xpath)
{
    return parseCityHoursSchedule($xpath, 2);
}

function getNumberOfScheduleRoutes(&$pdo_object)
{
    $sql = "SELECT COUNT(id) AS counter FROM rotas";

    foreach ($pdo_object->query($sql) as $row) {
        return (int) $row["counter"];
    }
}

function getScheduleIds(&$pdo_object, $begin)
{
    settype($begin, "int");

    $sql = "SELECT id FROM rotas ORDER BY id ASC LIMIT " . $begin . ", " . \Scraping\Config\NUMBER_OF_PROCESSES;
    $result = array();

    foreach ($pdo_object->query($sql) as $row) {
        $result[] = $row["id"];
    }

    return $result;
}

function insertToDatabaseSchedule(&$fields_gotten, &$pdo_object)
{
    if ($fields_gotten == false) {
        return false;
    }

    $sql = 'INSERT INTO ' . \Scraping\Config\DATABASE_ROUTES_SCHEDULE_TABLE . '(' . implode(', ', array_keys($fields_gotten)) . ') VALUES (';

    $values = array_values($fields_gotten);

    foreach ($values as &$value) {
        $value = '"' . $value . '"';
    }
    
    $sql .= implode(', ', $values) . ')';

    return $pdo_object->exec($sql);
}

function insertToDatabasePrice(&$fields_gotten, &$pdo_object)
{
    if ($fields_gotten == false || $fields_gotten == array()) {
        return false;
    }

    $allOk = true;

    foreach ($fields_gotten as $fields) {
        $sql = 'INSERT INTO ' . \Scraping\Config\DATABASE_ROUTES_PRICE_TABLE . '(';

        $keys = array_keys($fields);

        foreach ($keys as &$key) {
            $key = "`" . $key . "`";
        }
        $sql .= implode(', ', $keys);
        
        $sql .= ') VALUES (';

        $values = array_values($fields);

        foreach ($values as &$value) {
            $value = '"' . $value . '"';
        }
        
        $sql .= implode(', ', $values) . ')';

        $allOk = $allOk && $pdo_object->exec($sql);
    }

    return $allOk;
}

function parsePriceFromAndTo($string)
{
    $result = array();
    $string = explode("-", $string);

    foreach ($string as &$part) {
        $part = explode("(", $part);
        $part[1] = str_replace(" ", "", $part[1]);
        $part = implode("(", $part);
    }

    return $string;
}

function parsePrice($result, $route_id)
{
    $dom = new \DOMDocument();
    $dom->loadHTML($result);
    $dom->saveHTML();

    $xpath = new \DOMXPath($dom);
    $query = '//tbody/tr/td';
    $entries = $xpath->query($query);

    $scrapped = array();

    foreach ($entries as $entry) {
        $scrapped[] = $entry->nodeValue;
    }

    // 0 -> cidade de - cidade para 1
    // 1 -> pavimentado 2
    // 2 -> implantado 3
    // 3 -> natural 4
    // 4 -> nao tarifado 5
    // 5 -> preco maximo 6

    $c = count($scrapped);
    $j = 0;
    $formatted = array();

    for ($i = 0; $i < $c; $i++) {
        switch (($i + 1) % 6) {
            case 1:
                $formatted[$j] = array();
                $formatted[$j]["rotas_id"] = $route_id;
                $fromAndTo = parsePriceFromAndTo($scrapped[$i]);
                $formatted[$j]["cidade_de"] = $fromAndTo[0];
                $formatted[$j]["cidade_para"] = $fromAndTo[1];
                break;
            case 2:
                $formatted[$j]["pavimentado"] = str_replace(",", ".", $scrapped[$i]);
                break;
            case 3:
                $formatted[$j]["implantado"] = str_replace(",", ".", $scrapped[$i]);
                break;
            case 4:
                $formatted[$j]["leito_natural"] = str_replace(",", ".", $scrapped[$i]);
                break;
            case 5:
                $formatted[$j]["nao_tarifado"] = str_replace(",", ".", $scrapped[$i]);
                break;
            case 0:
                $formatted[$j]["preco_maximo"] = str_replace(",", ".", $scrapped[$i]);
                $formatted[$j]["preco_maximo"] = str_replace("R$ ", "", $formatted[$j]["preco_maximo"]);
                $j++;
                break;
        }
    }

    return $formatted;
}

function parseSchedule($result, $route_id)
{
    settype($route_id, 'int');

	$dom = new \DOMDocument();
	$dom->loadHTML($result);
	$dom->saveHTML();

	$xpath = new \DOMXPath($dom);
	
	$fields_gotten = parseGeneralInformationSchedule($xpath);

    if ($fields_gotten === false) {
        return false;
    }

    $fields_gotten['id'] = $route_id;
    $fields_gotten['ida'] = parseStartCitySchedule($xpath);
    $fields_gotten['volta'] = parseDestinyCitySchedule($xpath);
    $fields_gotten['horarios_ida'] = parseStartCityHoursSchedule($xpath);
    $fields_gotten['horarios_volta'] = parseDestinyCityHoursSchedule($xpath);

    // unsetting here the "heavy" vars, just in case
    unset($xpath);
    unset($dom);
    unset($result);

    foreach ($fields_gotten as $index => $field) {
        $fields_gotten[$index] = trim(replaceAllWithSingleSpace(ucwords(strtolower($field))));
    }

    return $fields_gotten;
}

function parseAndInsert($result, $type, $route_id, $pdo_object)
{

	$type = strtolower($type);

	switch ($type) {
		case \Scraping\Config\SCHEDULE_FLAG:
			$fields_gotten = parseSchedule($result, $route_id);
            return (bool) insertToDatabaseSchedule($fields_gotten, $pdo_object);
			break;
		case \Scraping\Config\PRICE_FLAG:
			$fields_gotten = parsePrice($result, $route_id);
            return (bool) insertToDatabasePrice($fields_gotten, $pdo_object);
			break;
		default:
			return false;
			break;
	}
}