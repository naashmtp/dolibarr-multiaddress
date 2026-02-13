<?php
/* Copyright (C) 2025
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    lib/multiaddress.lib.php
 * \ingroup multiaddress
 * \brief   Library files with common functions for MultiAddress
 */

/**
 * Prepare admin pages header
 *
 * @return array Array of tabs
 */
function multiaddressAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("multiaddress@multiaddress");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/multiaddress/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/multiaddress/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'multiaddress');

	return $head;
}

/**
 * Get country name from ID
 *
 * @param  int $fk_pays Country ID
 * @return string       Country name
 */
function multiaddressGetCountryName($fk_pays)
{
	global $db;

	if (!$fk_pays) return '';

	$sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_country WHERE rowid = ".((int) $fk_pays);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		return $obj ? $obj->label : '';
	}
	return '';
}

/**
 * Format address for display
 *
 * @param  MultiAddress $address Address object
 * @return string                Formatted address HTML
 */
function multiaddressFormatAddress($address)
{
	$html = '';

	if ($address->name) {
		$html .= '<strong>'.$address->name.'</strong><br>';
	}

	if ($address->address) {
		$html .= $address->address.'<br>';
	}

	if ($address->zip || $address->town) {
		$html .= $address->zip.' '.$address->town;
		if ($address->fk_pays) {
			$country = multiaddressGetCountryName($address->fk_pays);
			if ($country) $html .= ', '.$country;
		}
		$html .= '<br>';
	}

	if ($address->phone) {
		$html .= '📞 '.$address->phone.'<br>';
	}

	return $html;
}

/**
 * Get icon for address type
 *
 * @param  string $type Type of address
 * @return string       Icon HTML
 */
function multiaddressGetTypeIcon($type)
{
	$icons = array(
		'facturation' => '🏢',
		'livraison'   => '📦',
		'boutique'    => '🏪'
	);

	return isset($icons[$type]) ? $icons[$type] : '📍';
}

/**
 * Geocode address using Nominatim (OpenStreetMap)
 *
 * @param  string $address Full address string
 * @return array           Array with 'lat' and 'lon' keys, or empty array
 */
function multiaddressGeocodeAddress($address)
{
	$result = array();

	if (empty($address)) return $result;

	$url = 'https://nominatim.openstreetmap.org/search?format=json&q='.urlencode($address).'&limit=1';

	// Set user agent (required by Nominatim)
	$options = array(
		'http' => array(
			'header' => "User-Agent: DolibarrMultiAddress/1.0\r\n"
		)
	);
	$context = stream_context_create($options);

	$response = @file_get_contents($url, false, $context);

	if ($response) {
		$data = json_decode($response, true);
		if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
			$result['lat'] = $data[0]['lat'];
			$result['lon'] = $data[0]['lon'];
		}
	}

	return $result;
}
