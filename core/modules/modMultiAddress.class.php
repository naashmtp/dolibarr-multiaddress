<?php
/* Copyright (C) 2025
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \defgroup   multiaddress Module MultiAddress
 * \brief      Module pour gérer plusieurs adresses par tiers (facturation, livraison, boutique)
 * \file       core/modules/modMultiAddress.class.php
 * \ingroup    multiaddress
 * \brief      Description and activation file for module MultiAddress
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module MultiAddress
 */
class modMultiAddress extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		$this->numero = 500000; // Unique ID for module
		$this->rights_class = 'multiaddress';
		$this->family = "other";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Module de gestion multi-adresses pour les tiers";
		$this->descriptionlong = "Permet de gérer plusieurs types d'adresses pour chaque tiers (facturation, livraison, boutique) avec interface ergonomique et géolocalisation.";

		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'generic';

		$this->dirs = array();

		$this->config_page_url = array("setup.php@multiaddress");

		$this->hidden = false;
		$this->depends = array('modSociete');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("multiaddress@multiaddress");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Cronjobs
		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Lire les adresses multiples';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Créer/modifier les adresses multiples';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Supprimer les adresses multiples';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$this->rights[$r][5] = '';

		// Main menu entries
		$this->menu = array();

		// Tabs
		$this->tabs = array(
			'thirdparty:+multiaddress:MultiAddresses:multiaddress@multiaddress:$user->rights->societe->lire:/multiaddress/address.php?socid=__ID__'
		);

		// Hooks
		$this->module_parts = array(
			'hooks' => array(
				'invoicecard',
				'ordercard',
				'expeditioncard',
				'propalcard',
				'pdfgeneration'
			)
		);
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/multiaddress/sql/');
		if ($result < 0) {
			return -1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options Options when disabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
