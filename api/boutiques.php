<?php
/* Copyright (C) 2025
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    api/boutiques.php
 * \ingroup multiaddress
 * \brief   API REST pour exporter les boutiques au format GeoJSON
 */

if (file_exists("../../main.inc.php")) {
	require '../../main.inc.php';
} else {
	die("Include of main fails");
}

require_once __DIR__.'/../core/classes/address.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

header('Content-Type: application/json; charset=utf-8');

// CORS restreint aux domaines autorises
$allowed_origins = array(
	// 'https://yourmap.example.com',
	// 'https://www.yourmap.example.com',
	// 'https://yourdomain.example.com',
	// 'https://www.yourdomain.example.com'
);
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
	header('Access-Control-Allow-Origin: ' . $origin);
} else {
	header('Access-Control-Allow-Origin: *');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header('Access-Control-Allow-Methods: GET');
	header('Access-Control-Allow-Headers: Content-Type');
	header('Access-Control-Max-Age: 86400');
	http_response_code(204);
	exit;
}

$visible_only = GETPOST('visible_only', 'int'); // Filter only visible_vogliomap=1
$format = GETPOST('format', 'alpha'); // json or geojson

// Security check - cle API obligatoire
$apikey = GETPOST('apikey', 'alpha');
$expected_key = !empty($conf->global->MULTIADDRESS_API_KEY) ? $conf->global->MULTIADDRESS_API_KEY : '';
if (empty($expected_key) || empty($apikey) || $apikey !== $expected_key) {
	http_response_code(403);
	echo json_encode(array('error' => 'Unauthorized - API key required'));
	$db->close();
	exit;
}

$sql = "SELECT a.*, s.nom as societe_nom";
$sql .= " FROM ".MAIN_DB_PREFIX."societe_address as a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = a.fk_soc";
$sql .= " WHERE a.type = 'boutique'";
$sql .= " AND a.status = 1";

if ($visible_only) {
	$sql .= " AND a.visible_vogliomap = 1";
}

$sql .= " ORDER BY s.nom";

$resql = $db->query($sql);

$boutiques = array();

if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$boutique = array(
			'id' => $obj->rowid,
			'nom' => $obj->societe_nom ?: $obj->name,
			'label' => $obj->label,
			'adresse' => $obj->address,
			'code_postal' => $obj->zip,
			'ville' => $obj->town,
			'telephone' => $obj->phone,
			'latitude' => $obj->latitude ? floatval($obj->latitude) : null,
			'longitude' => $obj->longitude ? floatval($obj->longitude) : null,
			'visible_vogliomap' => $obj->visible_vogliomap ? true : false
		);

		$boutiques[] = $boutique;
	}
	$db->free($resql);
}

if ($format == 'geojson') {
	$geojson = array(
		'type' => 'FeatureCollection',
		'features' => array()
	);

	foreach ($boutiques as $boutique) {
		if ($boutique['latitude'] && $boutique['longitude']) {
			$feature = array(
				'type' => 'Feature',
				'geometry' => array(
					'type' => 'Point',
					'coordinates' => array($boutique['longitude'], $boutique['latitude'])
				),
				'properties' => array(
					'id' => $boutique['id'],
					'nom' => $boutique['nom'],
					'label' => $boutique['label'],
					'adresse' => $boutique['adresse'],
					'code_postal' => $boutique['code_postal'],
					'ville' => $boutique['ville'],
					'telephone' => $boutique['telephone']
				)
			);
			$geojson['features'][] = $feature;
		}
	}

	echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
	$output = array(
		'success' => true,
		'count' => count($boutiques),
		'boutiques' => $boutiques
	);

	echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$db->close();
