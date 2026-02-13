<?php
/**
 * Script de migration optionnel : Convertir les revendeurs en adresses "boutique"
 *
 * Ce script permet de migrer progressivement les revendeurs depuis llxbm_societe
 * vers des adresses de type "boutique" dans llxbm_societe_address
 *
 * Usage:
 *   php migrate_revendeurs_to_boutiques.php [--all] [--dry-run] [--soc-id=123]
 *
 * Options:
 *   --all       : Migrer TOUS les revendeurs actifs (431)
 *   --dry-run   : Simulation sans écriture en base
 *   --soc-id=X  : Migrer uniquement la société X
 *   --limit=N   : Limiter à N sociétés (pour tests)
 *
 * Exemples:
 *   php migrate_revendeurs_to_boutiques.php --dry-run --limit=10
 *   php migrate_revendeurs_to_boutiques.php --soc-id=123
 *   php migrate_revendeurs_to_boutiques.php --all
 *
 * @author
 * @date    2025-10-28
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) {
    $res = include '../../../main.inc.php';
}
if (!$res && file_exists("../../../../main.inc.php")) {
    $res = include '../../../../main.inc.php';
}
if (!$res) {
    die("❌ Erreur: Impossible de charger l'environnement Dolibarr\n");
}

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once __DIR__.'/../core/classes/address.class.php';

$options = getopt("", ["all", "dry-run", "soc-id:", "limit:"]);
$dryRun = isset($options['dry-run']);
$migrateAll = isset($options['all']);
$socId = isset($options['soc-id']) ? intval($options['soc-id']) : null;
$limit = isset($options['limit']) ? intval($options['limit']) : null;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  MIGRATION REVENDEURS → ADRESSES BOUTIQUES                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "🔍 MODE SIMULATION (--dry-run) - Aucune modification en base\n\n";
}

if (!$migrateAll && !$socId && !$limit) {
    echo "❌ ERREUR: Vous devez spécifier une option:\n";
    echo "   --all         : Migrer tous les revendeurs\n";
    echo "   --soc-id=X    : Migrer uniquement la société X\n";
    echo "   --limit=N     : Migrer N sociétés (pour test)\n";
    echo "   --dry-run     : Simulation sans écriture\n\n";
    echo "Exemple: php migrate_revendeurs_to_boutiques.php --dry-run --limit=5\n";
    exit(1);
}

$sql = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.fk_pays,";
$sql .= " s.phone, s.latitude, s.longitude, s.url";
$sql .= " FROM ".MAIN_DB_PREFIX."societe s";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."commande cmd ON cmd.fk_soc = s.rowid";
$sql .= " WHERE s.fk_typent = 3";
$sql .= " AND s.fournisseur != 1";
$sql .= " AND cmd.date_commande >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";

if ($socId) {
    $sql .= " AND s.rowid = ".((int) $socId);
}

$sql .= " GROUP BY s.rowid";
$sql .= " ORDER BY s.nom";

if ($limit) {
    $sql .= " LIMIT ".((int) $limit);
}

$resql = $db->query($sql);
if (!$resql) {
    die("❌ Erreur SQL: ".$db->lasterror()."\n");
}

$totalRevendeurs = $db->num_rows($resql);
echo "📊 Revendeurs à traiter: $totalRevendeurs\n\n";

if ($totalRevendeurs == 0) {
    echo "ℹ️  Aucun revendeur à migrer.\n";
    exit(0);
}

if ($migrateAll && !$dryRun) {
    echo "⚠️  ATTENTION: Vous allez migrer $totalRevendeurs revendeurs!\n";
    echo "   Cela va créer $totalRevendeurs adresses de type 'boutique'.\n";
    echo "   Cette opération est irréversible.\n\n";
    echo "Continuer? (tapez 'oui' pour confirmer): ";
    $confirm = trim(fgets(STDIN));
    if (strtolower($confirm) !== 'oui') {
        echo "❌ Migration annulée.\n";
        exit(0);
    }
    echo "\n";
}

$stats = [
    'success' => 0,
    'skipped' => 0,
    'errors' => 0,
    'created' => 0
];

echo "🚀 Début de la migration...\n\n";

// Traitement de chaque revendeur
while ($obj = $db->fetch_object($resql)) {
    echo "┌─ [{$obj->rowid}] {$obj->nom}\n";

    // Vérifier si une adresse boutique existe déjà
    $sqlCheck = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_address";
    $sqlCheck .= " WHERE fk_soc = ".((int) $obj->rowid);
    $sqlCheck .= " AND type = 'boutique'";
    $sqlCheck .= " AND status = 1";

    $resCheck = $db->query($sqlCheck);
    if ($db->num_rows($resCheck) > 0) {
        echo "│  ⏭️  SKIP: Adresse boutique déjà existante\n";
        echo "└─────────────────────────────────────\n";
        $stats['skipped']++;
        continue;
    }

    // Vérifier que les données essentielles existent
    if (empty($obj->address) || empty($obj->town)) {
        echo "│  ⚠️  SKIP: Adresse incomplète (address ou town manquant)\n";
        echo "└─────────────────────────────────────\n";
        $stats['skipped']++;
        continue;
    }

    // Créer l'adresse boutique
    if (!$dryRun) {
        $address = new MultiAddress($db);
        $address->fk_soc = $obj->rowid;
        $address->type = 'boutique';
        $address->is_default = 1;
        $address->label = 'Boutique principale';
        $address->name = $obj->nom;
        $address->address = $obj->address;
        $address->zip = $obj->zip;
        $address->town = $obj->town;
        $address->fk_pays = $obj->fk_pays;
        $address->phone = $obj->phone;
        $address->latitude = $obj->latitude;
        $address->longitude = $obj->longitude;
        $address->visible_vogliomap = 1; // Visible par défaut
        $address->status = 1;

        $result = $address->create($user);

        if ($result > 0) {
            echo "│  ✅ Adresse boutique créée (ID: $result)\n";
            echo "│     Adresse: {$obj->address}\n";
            echo "│     Ville: {$obj->town}\n";
            if ($obj->latitude && $obj->longitude) {
                echo "│     GPS: {$obj->latitude}, {$obj->longitude}\n";
            } else {
                echo "│     ⚠️  GPS manquant (géocodage requis)\n";
            }
            $stats['created']++;
            $stats['success']++;
        } else {
            echo "│  ❌ ERREUR: {$address->error}\n";
            $stats['errors']++;
        }
    } else {
        echo "│  🔍 [DRY-RUN] Créerait une adresse boutique\n";
        echo "│     Adresse: {$obj->address}, {$obj->zip} {$obj->town}\n";
        echo "│     GPS: ".($obj->latitude ?: 'manquant').", ".($obj->longitude ?: 'manquant')."\n";
        $stats['created']++;
    }

    echo "└─────────────────────────────────────\n";
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  RAPPORT DE MIGRATION                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
echo "✅ Créées avec succès : {$stats['created']}\n";
echo "⏭️  Déjà existantes     : {$stats['skipped']}\n";
echo "❌ Erreurs             : {$stats['errors']}\n";
echo "──────────────────────────────────\n";
echo "📊 TOTAL traité        : $totalRevendeurs\n\n";

if ($dryRun) {
    echo "ℹ️  Mode simulation - Relancez sans --dry-run pour appliquer les changements.\n\n";
} else {
    echo "✅ Migration terminée avec succès!\n\n";
    echo "📋 PROCHAINES ÉTAPES:\n";
    echo "   1. Vérifiez votre carte pour confirmer les boutiques\n";
    echo "   2. Les nouvelles boutiques devraient apparaître automatiquement\n";
    echo "   3. Vous pouvez maintenant gérer les adresses depuis Dolibarr\n";
    echo "      (Fiche tiers → Onglet 'Adresses')\n\n";
}

$db->close();
