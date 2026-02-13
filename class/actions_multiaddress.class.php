<?php
/* Copyright (C) 2025
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    class/actions_multiaddress.class.php
 * \ingroup multiaddress
 * \brief   Hook file to override address behavior in PDF generation
 */

require_once DOL_DOCUMENT_ROOT.'/custom/multiaddress/core/classes/address.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

/**
 * Class ActionsMultiAddress
 */
class ActionsMultiAddress
{
	/** @var DoliDB */
	public $db;

	/** @var array Hook results */
	public $results = array();

	/** @var string String displayed by executeHook() immediately after return */
	public $resprints;

	/** @var array Errors */
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Hook executed BEFORE PDF creation
	 * Modifies thirdparty name based on multiaddress label
	 *
	 * @param array         $parameters Hook parameters
	 * @param CommonObject  $object     The document object (Facture, Commande, etc.)
	 * @param string        $action     Current action
	 * @return int                      0=OK
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf;

		dol_syslog("MultiAddress Hook: beforePDFCreation called for element=".(is_object($object) ? $object->element : 'none'), LOG_DEBUG);

		if (!is_object($object) || !is_object($object->thirdparty)) {
			return 0;
		}

		$addressType = $this->getAddressTypeForDocument($object);
		if (!$addressType) {
			return 0;
		}

		dol_syslog("MultiAddress Hook: beforePDFCreation looking for address type '$addressType' for thirdparty ".$object->thirdparty->id, LOG_DEBUG);

		$multiAddress = new MultiAddress($this->db);
		$defaultAddress = $multiAddress->getDefaultAddress($object->thirdparty->id, $addressType);

		if (!$defaultAddress) {
			return 0;
		}

		// Si le libellé est rempli, l'utiliser comme nom du tiers
		if (!empty($defaultAddress->label)) {
			dol_syslog("MultiAddress Hook: Replacing thirdparty name '".$object->thirdparty->name."' with label '".$defaultAddress->label."'", LOG_DEBUG);
			$object->thirdparty->name = $defaultAddress->label;
		}

		return 0;
	}

	/**
	 * Hook executed when building PDF address
	 * This hook intercepts the address building for PDF generation
	 * and replaces it with the appropriate address from MultiAddress
	 *
	 * @param array         $parameters Hook parameters
	 * @param CommonObject  $object     The object to process (Facture, Commande, Expedition, etc.)
	 * @param string        $action     Current action
	 * @return int                      0=Continue, 1=Replace default behavior
	 */
	public function pdf_build_address($parameters, &$object, &$action)
	{
		global $conf;

		dol_syslog("MultiAddress Hook: pdf_build_address called with mode='".$parameters['mode']."'", LOG_DEBUG);

		$mode = $parameters['mode'];
		$targetcompany = $parameters['targetcompany'];
		$outputlangs = $parameters['outputlangs'];

		if (!is_object($targetcompany)) {
			return 0;
		}
		if ($mode == 'source') {
			return 0;
		}
		if (strpos($mode, 'target') !== 0) {
			return 0;
		}

		$addressType = $this->getAddressTypeForDocument($object);
		if (!$addressType) {
			return 0;
		}

		dol_syslog("MultiAddress Hook: Looking for address type '$addressType' for third party ".$targetcompany->id, LOG_DEBUG);

		$multiAddress = new MultiAddress($this->db);
		$defaultAddress = $multiAddress->getDefaultAddress($targetcompany->id, $addressType);

		if (!$defaultAddress) {
			dol_syslog("MultiAddress Hook: No default address found for type '$addressType', using Dolibarr default", LOG_DEBUG);
			return 0;
		}

		dol_syslog("MultiAddress Hook: Using MultiAddress ID ".$defaultAddress->id." for PDF", LOG_DEBUG);

		// Si le libellé de l'adresse est rempli, l'utiliser comme nom du tiers sur le document
		if ($defaultAddress->label) {
			$targetcompany->name = $defaultAddress->label;
		} elseif ($defaultAddress->name) {
			$targetcompany->name = $defaultAddress->name;
		}
		if ($defaultAddress->address) {
			$targetcompany->address = $defaultAddress->address;
		}
		if ($defaultAddress->zip) {
			$targetcompany->zip = $defaultAddress->zip;
		}
		if ($defaultAddress->town) {
			$targetcompany->town = $defaultAddress->town;
		}
		if ($defaultAddress->fk_pays) {
			$targetcompany->country_id = $defaultAddress->fk_pays;
			$targetcompany->country_code = getCountry($defaultAddress->fk_pays, 2);
		}
		if ($defaultAddress->phone) {
			$targetcompany->phone = $defaultAddress->phone;
		}
		if ($defaultAddress->fax) {
			$targetcompany->fax = $defaultAddress->fax;
		}

		return 0;
	}

	/**
	 * Determine which address type to use based on document type
	 *
	 * @param  CommonObject $object Document object
	 * @return string|null          Address type ('facturation', 'livraison', 'boutique') or null
	 */
	private function getAddressTypeForDocument($object)
	{
		if (!is_object($object)) {
			return null;
		}

		$element = $object->element;

		$mapping = array(
			'facture' => 'facturation',
			'invoice' => 'facturation',
			'commande' => 'livraison',
			'order' => 'livraison',
			'expedition' => 'livraison',
			'shipping' => 'livraison',
			'propal' => 'facturation',
			'proposal' => 'facturation',
			'supplier_order' => 'livraison',
			'supplier_invoice' => 'facturation',
		);

		if (isset($mapping[$element])) {
			return $mapping[$element];
		}

		return null;
	}
}
