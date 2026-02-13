<?php
/* Copyright (C) 2025
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    address.php
 * \ingroup multiaddress
 * \brief   Page de gestion des adresses multiples d'un tiers
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = include '../../main.inc.php';
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = include '../../../main.inc.php';
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once __DIR__.'/core/classes/address.class.php';
require_once __DIR__.'/lib/multiaddress.lib.php';

$langs->loadLangs(array("companies", "multiaddress@multiaddress"));

$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

if ($socid < 0) $socid = '';

$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);
$object->fetch($socid);

$address = new MultiAddress($db);

/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
		$address->fetch($id);
		$result = $address->delete($user);

		if ($result > 0) {
			setEventMessages($langs->trans("AddressDeleted"), null, 'mesgs');
			header("Location: ".$_SERVER['PHP_SELF'].'?socid='.$socid);
			exit;
		} else {
			setEventMessages($address->error, $address->errors, 'errors');
			$action = '';
		}
	}

	if (($action == 'add' || $action == 'update') && !$cancel && $user->hasRight('societe', 'creer')) {
		$error = 0;

		if (empty(GETPOST('address_type'))) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AddressType")), null, 'errors');
			$error++;
			$action = ($action == 'add' ? 'create' : 'edit');
		}

		if (!$error) {
			if ($action == 'add') {
				$address = new MultiAddress($db);
			} else {
				$address->fetch($id);
			}

			$address->fk_soc = $socid;
			$address->type = GETPOST('address_type', 'alpha');
			$address->is_default = GETPOST('is_default', 'int') ? 1 : 0;
			$address->label = GETPOST('label', 'alpha');
			$address->name = GETPOST('name', 'alpha');
			$address->address = GETPOST('address_addr', 'alpha');
			$address->zip = GETPOST('zipcode', 'alpha');
			$address->town = GETPOST('town', 'alpha');
			$address->fk_pays = GETPOST('country_id', 'int');
			$address->phone = GETPOST('phone', 'alpha');
			$address->fax = GETPOST('fax', 'alpha');
			$address->note = GETPOST('note', 'restricthtml');
			$address->latitude = GETPOST('latitude', 'alpha');
			$address->longitude = GETPOST('longitude', 'alpha');
			$address->visible_vogliomap = GETPOST('visible_vogliomap', 'int') ? 1 : 0;
			$address->status = 1;

			if ($action == 'add') {
				$result = $address->create($user);
			} else {
				$result = $address->update($user);
			}

			if ($result > 0) {
				setEventMessages($action == 'add' ? $langs->trans("AddressCreated") : $langs->trans("AddressUpdated"), null, 'mesgs');
				header("Location: ".$_SERVER['PHP_SELF'].'?socid='.$socid);
				exit;
			} else {
				setEventMessages($address->error, $address->errors, 'errors');
				$action = ($action == 'add' ? 'create' : 'edit');
			}
		}
	}
}

/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("MultiAddresses").' - '.$object->name;
$help_url = '';

llxHeader('', $title, $help_url);

if ($socid > 0) {
	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'multiaddress', $langs->trans("ThirdParty"), -1, 'company');

	dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	if ($action == 'delete') {
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid, $langs->trans('DeleteAddress'), $langs->trans('ConfirmDeleteAddress'), 'confirm_delete', '', 0, 1);
	}

	if ($action == 'create' || $action == 'edit') {
		if ($action == 'edit') {
			$address->fetch($id);
		}

		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.($action == 'create' ? 'add' : 'update').'">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		if ($action == 'edit') {
			print '<input type="hidden" name="id" value="'.$id.'">';
		}

		print '<table class="border centpercent">';

		print '<tr><td class="fieldrequired">'.$langs->trans("AddressType").'</td><td>';
		print '<select name="address_type" class="flat minwidth200">';
		$selected_type = ($action == 'edit') ? $address->type : GETPOST('address_type');
		print '<option value="facturation"'.($selected_type == 'facturation' ? ' selected' : '').'>🏢 '.$langs->trans("Facturation").'</option>';
		print '<option value="livraison"'.($selected_type == 'livraison' ? ' selected' : '').'>📦 '.$langs->trans("Livraison").'</option>';
		print '<option value="boutique"'.($selected_type == 'boutique' ? ' selected' : '').'>🏪 '.$langs->trans("Boutique").'</option>';
		print '</select>';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("IsDefault").'</td><td>';
		print '<input type="checkbox" name="is_default" value="1"'.($address->is_default ? ' checked' : '').'>';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressLabel").'</td><td>';
		print '<input type="text" name="label" class="flat minwidth300" value="'.dol_escape_htmltag($address->label).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressName").'</td><td>';
		print '<input type="text" name="name" class="flat minwidth300" value="'.dol_escape_htmltag($address->name).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressAddress").'</td><td>';
		print '<textarea name="address_addr" class="flat minwidth300" rows="2">'.dol_escape_htmltag($address->address).'</textarea>';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressZip").'</td><td>';
		print '<input type="text" name="zipcode" class="flat minwidth100" value="'.dol_escape_htmltag($address->zip).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressTown").'</td><td>';
		print '<input type="text" name="town" class="flat minwidth200" value="'.dol_escape_htmltag($address->town).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressCountry").'</td><td>';
		print $form->select_country($address->fk_pays ? $address->fk_pays : '', 'country_id');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressPhone").'</td><td>';
		print '<input type="text" name="phone" class="flat minwidth200" value="'.dol_escape_htmltag($address->phone).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AddressFax").'</td><td>';
		print '<input type="text" name="fax" class="flat minwidth200" value="'.dol_escape_htmltag($address->fax).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Geolocation").'</td><td>';
		print 'Lat: <input type="text" name="latitude" class="flat" size="10" value="'.dol_escape_htmltag($address->latitude).'">';
		print ' Lon: <input type="text" name="longitude" class="flat" size="10" value="'.dol_escape_htmltag($address->longitude).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("VisibleVoglioMap").'</td><td>';
		print '<input type="checkbox" name="visible_vogliomap" value="1"'.($address->visible_vogliomap ? ' checked' : '').'>';
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("AddressNote").'</td><td>';
		print '<textarea name="note" class="flat minwidth300" rows="3">'.dol_escape_htmltag($address->note).'</textarea>';
		print '</td></tr>';

		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; ';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print '</form>';
	}

	if ($action != 'create' && $action != 'edit') {
		$addresses = $address->fetchAll($socid);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("AddressType").'</th>';
		print '<th>'.$langs->trans("AddressLabel").'</th>';
		print '<th>'.$langs->trans("AddressAddress").'</th>';
		print '<th>'.$langs->trans("AddressTown").'</th>';
		print '<th class="center">'.$langs->trans("IsDefault").'</th>';
		print '<th class="center">'.$langs->trans("VisibleVoglioMap").'</th>';
		print '<th class="right">'.$langs->trans("Action").'</th>';
		print '</tr>';

		if (count($addresses) > 0) {
			foreach ($addresses as $addr) {
				print '<tr class="oddeven">';

				print '<td>';
				print multiaddressGetTypeIcon($addr->type).' ';
				print $langs->trans(ucfirst($addr->type));
				print '</td>';

				print '<td>';
				print dol_escape_htmltag($addr->label);
				if ($addr->is_default) {
					print ' <span class="badge badge-status4">⭐ '.$langs->trans("IsDefault").'</span>';
				}
				print '</td>';

				print '<td>';
				print dol_escape_htmltag($addr->address);
				print '</td>';

				print '<td>';
				print dol_escape_htmltag($addr->zip).' '.dol_escape_htmltag($addr->town);
				print '</td>';

				print '<td class="center">';
				print $addr->is_default ? '✓' : '';
				print '</td>';

				print '<td class="center">';
				print $addr->visible_vogliomap ? '✓' : '';
				print '</td>';

				print '<td class="right">';
				print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$addr->id.'&socid='.$socid.'">'.img_edit().'</a>';
				print ' ';
				print '<a class="deletefielda" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$addr->id.'&socid='.$socid.'">'.img_delete().'</a>';
				print '</td>';

				print '</tr>';
			}
		} else {
			print '<tr><td colspan="7" class="opacitymedium">'.$langs->trans("NoAddressFound").'</td></tr>';
		}

		print '</table>';
		print '</div>';

		print '<div class="tabsAction">';
		if ($user->hasRight('societe', 'creer')) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=create&socid='.$socid.'">'.$langs->trans("AddAddress").'</a>';
		}
		print '</div>';
	}

	print '</div>';

	print dol_get_fiche_end();
}

llxFooter();
$db->close();
