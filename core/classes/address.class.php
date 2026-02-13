<?php
/* Copyright (C) 2025
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * \file    core/classes/address.class.php
 * \ingroup multiaddress
 * \brief   Classe de gestion des adresses multiples pour tiers
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Classe MultiAddress
 */
class MultiAddress extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'multiaddress';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'societe_address';

	/**
	 * @var int ID
	 */
	public $id;

	public $rowid;
	public $fk_soc;
	public $type;           // 'facturation', 'livraison', 'boutique'
	public $is_default;
	public $label;
	public $name;
	public $address;
	public $zip;
	public $town;
	public $fk_pays;
	public $phone;
	public $fax;
	public $note;
	public $latitude;
	public $longitude;
	public $visible_vogliomap;
	public $status;
	public $datec;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;

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
	 * Create address in database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = false)
	{
		global $conf;

		// Si cette adresse est marquée par défaut, désactiver les autres adresses par défaut du même type
		if ($this->is_default) {
			$this->unsetDefaultAddress($this->fk_soc, $this->type);
		}

		$now = dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "fk_soc,";
		$sql .= "type,";
		$sql .= "is_default,";
		$sql .= "label,";
		$sql .= "name,";
		$sql .= "address,";
		$sql .= "zip,";
		$sql .= "town,";
		$sql .= "fk_pays,";
		$sql .= "phone,";
		$sql .= "fax,";
		$sql .= "note,";
		$sql .= "latitude,";
		$sql .= "longitude,";
		$sql .= "visible_vogliomap,";
		$sql .= "status,";
		$sql .= "datec,";
		$sql .= "fk_user_creat";
		$sql .= ") VALUES (";
		$sql .= " ".((int) $this->fk_soc).",";
		$sql .= " '".$this->db->escape($this->type)."',";
		$sql .= " ".((int) $this->is_default).",";
		$sql .= " ".($this->label ? "'".$this->db->escape($this->label)."'" : "NULL").",";
		$sql .= " ".($this->name ? "'".$this->db->escape($this->name)."'" : "NULL").",";
		$sql .= " ".($this->address ? "'".$this->db->escape($this->address)."'" : "NULL").",";
		$sql .= " ".($this->zip ? "'".$this->db->escape($this->zip)."'" : "NULL").",";
		$sql .= " ".($this->town ? "'".$this->db->escape($this->town)."'" : "NULL").",";
		$sql .= " ".($this->fk_pays > 0 ? (int) $this->fk_pays : "NULL").",";
		$sql .= " ".($this->phone ? "'".$this->db->escape($this->phone)."'" : "NULL").",";
		$sql .= " ".($this->fax ? "'".$this->db->escape($this->fax)."'" : "NULL").",";
		$sql .= " ".($this->note ? "'".$this->db->escape($this->note)."'" : "NULL").",";
		$sql .= " ".($this->latitude ? (float) $this->latitude : "NULL").",";
		$sql .= " ".($this->longitude ? (float) $this->longitude : "NULL").",";
		$sql .= " ".((int) $this->visible_vogliomap).",";
		$sql .= " ".((int) $this->status).",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " ".((int) $user->id);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

		$this->db->commit();
		return $this->id;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 * @return int       <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_soc,";
		$sql .= " t.type,";
		$sql .= " t.is_default,";
		$sql .= " t.label,";
		$sql .= " t.name,";
		$sql .= " t.address,";
		$sql .= " t.zip,";
		$sql .= " t.town,";
		$sql .= " t.fk_pays,";
		$sql .= " t.phone,";
		$sql .= " t.fax,";
		$sql .= " t.note,";
		$sql .= " t.latitude,";
		$sql .= " t.longitude,";
		$sql .= " t.visible_vogliomap,";
		$sql .= " t.status,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id               = $obj->rowid;
				$this->rowid            = $obj->rowid;
				$this->fk_soc           = $obj->fk_soc;
				$this->type             = $obj->type;
				$this->is_default       = $obj->is_default;
				$this->label            = $obj->label;
				$this->name             = $obj->name;
				$this->address          = $obj->address;
				$this->zip              = $obj->zip;
				$this->town             = $obj->town;
				$this->fk_pays          = $obj->fk_pays;
				$this->phone            = $obj->phone;
				$this->fax              = $obj->fax;
				$this->note             = $obj->note;
				$this->latitude         = $obj->latitude;
				$this->longitude        = $obj->longitude;
				$this->visible_vogliomap = $obj->visible_vogliomap;
				$this->status           = $obj->status;
				$this->datec            = $this->db->jdate($obj->datec);
				$this->tms              = $this->db->jdate($obj->tms);
				$this->fk_user_creat    = $obj->fk_user_creat;
				$this->fk_user_modif    = $obj->fk_user_modif;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = false)
	{
		// Si cette adresse est marquée par défaut, désactiver les autres
		if ($this->is_default) {
			$this->unsetDefaultAddress($this->fk_soc, $this->type, $this->id);
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " type = '".$this->db->escape($this->type)."',";
		$sql .= " is_default = ".((int) $this->is_default).",";
		$sql .= " label = ".($this->label ? "'".$this->db->escape($this->label)."'" : "NULL").",";
		$sql .= " name = ".($this->name ? "'".$this->db->escape($this->name)."'" : "NULL").",";
		$sql .= " address = ".($this->address ? "'".$this->db->escape($this->address)."'" : "NULL").",";
		$sql .= " zip = ".($this->zip ? "'".$this->db->escape($this->zip)."'" : "NULL").",";
		$sql .= " town = ".($this->town ? "'".$this->db->escape($this->town)."'" : "NULL").",";
		$sql .= " fk_pays = ".($this->fk_pays > 0 ? (int) $this->fk_pays : "NULL").",";
		$sql .= " phone = ".($this->phone ? "'".$this->db->escape($this->phone)."'" : "NULL").",";
		$sql .= " fax = ".($this->fax ? "'".$this->db->escape($this->fax)."'" : "NULL").",";
		$sql .= " note = ".($this->note ? "'".$this->db->escape($this->note)."'" : "NULL").",";
		$sql .= " latitude = ".($this->latitude ? (float) $this->latitude : "NULL").",";
		$sql .= " longitude = ".($this->longitude ? (float) $this->longitude : "NULL").",";
		$sql .= " visible_vogliomap = ".((int) $this->visible_vogliomap).",";
		$sql .= " status = ".((int) $this->status).",";
		$sql .= " fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = false)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Fetch all addresses for a third party
	 *
	 * @param  int    $fk_soc  ID of third party
	 * @param  string $type    Filter by type (optional)
	 * @return array           Array of MultiAddress objects
	 */
	public function fetchAll($fk_soc, $type = '')
	{
		$addresses = array();

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_soc = ".((int) $fk_soc);
		if ($type) {
			$sql .= " AND type = '".$this->db->escape($type)."'";
		}
		$sql .= " ORDER BY is_default DESC, type, label";

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$address = new MultiAddress($this->db);
				$address->fetch($obj->rowid);
				$addresses[] = $address;
			}
			$this->db->free($resql);
		}

		return $addresses;
	}

	/**
	 * Get default address for a type
	 *
	 * @param  int    $fk_soc  ID of third party
	 * @param  string $type    Type of address
	 * @return MultiAddress|null Address object or null
	 */
	public function getDefaultAddress($fk_soc, $type)
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_soc = ".((int) $fk_soc);
		$sql .= " AND type = '".$this->db->escape($type)."'";
		$sql .= " AND is_default = 1";
		$sql .= " LIMIT 1";

		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0) {
			$obj = $this->db->fetch_object($resql);
			$this->fetch($obj->rowid);
			return $this;
		}

		return null;
	}

	/**
	 * Unset default flag for all addresses of same type
	 *
	 * @param  int    $fk_soc     ID of third party
	 * @param  string $type       Type of address
	 * @param  int    $except_id  ID to exclude (when updating)
	 * @return int
	 */
	private function unsetDefaultAddress($fk_soc, $type, $except_id = 0)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET is_default = 0";
		$sql .= " WHERE fk_soc = ".((int) $fk_soc);
		$sql .= " AND type = '".$this->db->escape($type)."'";
		if ($except_id > 0) {
			$sql .= " AND rowid != ".((int) $except_id);
		}

		$resql = $this->db->query($sql);
		return $resql ? 1 : -1;
	}

	/**
	 * Get label for address type
	 *
	 * @param  string $type Type of address
	 * @return string       Label
	 */
	public static function getTypeLabel($type)
	{
		$labels = array(
			'facturation' => '🏢 Facturation',
			'livraison'   => '📦 Livraison',
			'boutique'    => '🏪 Boutique'
		);

		return isset($labels[$type]) ? $labels[$type] : $type;
	}
}
