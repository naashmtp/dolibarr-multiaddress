<?php
/* Copyright (C) 2025
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    admin/setup.php
 * \ingroup multiaddress
 * \brief   MultiAddress setup page
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) {
	$res = include '../../../main.inc.php';
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = include '../../../../main.inc.php';
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/multiaddress.lib.php';

$langs->loadLangs(array("admin", "multiaddress@multiaddress"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

if ($action == 'setmod') {
	// TODO: Set module options here
}

/*
 * View
 */

$page_name = "MultiAddressSetup";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = multiaddressAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, 'multiaddress@multiaddress');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setmod">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("MultiAddressEnabled").'</td>';
print '<td class="right">';
print '<span class="badge badge-status4 badge-status">✓ '.$langs->trans("Enabled").'</span>';
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

print '<br>';
print '<div class="center">';
print '<span class="opacitymedium">'.$langs->trans("MultiAddressInfo").'</span>';
print '</div>';

llxFooter();
$db->close();
