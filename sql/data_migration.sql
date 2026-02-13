-- ============================================================================
-- Migration des données existantes vers le système multi-adresses
-- ============================================================================

-- 1. MIGRATION DES ADRESSES PRINCIPALES (facturation par défaut)
-- Copie les adresses principales des sociétés vers societe_address
INSERT INTO llx_societe_address
    (fk_soc, type, is_default, name, address, zip, town, fk_pays, phone, fax, datec, fk_user_creat)
SELECT
    s.rowid,
    'facturation',
    1,
    s.nom,
    s.address,
    s.zip,
    s.town,
    s.fk_pays,
    s.phone,
    s.fax,
    NOW(),
    s.fk_user_creat
FROM llx_societe s
LEFT JOIN llx_societe_address sa ON sa.fk_soc = s.rowid AND sa.type = 'facturation'
WHERE s.address IS NOT NULL
  AND s.address != ''
  AND sa.rowid IS NULL;  -- Ne pas dupliquer si déjà migré

-- 2. MIGRATION DU CHAMP ADRESSEDELIVRAISON (livraison)
-- Copie les adresses de livraison depuis les extrafields
INSERT INTO llx_societe_address
    (fk_soc, type, is_default, name, address, datec, fk_user_creat)
SELECT
    se.fk_object,
    'livraison',
    1,
    s.nom,
    se.adressedelivraison,
    NOW(),
    s.fk_user_creat
FROM llx_societe_extrafields se
INNER JOIN llx_societe s ON s.rowid = se.fk_object
LEFT JOIN llx_societe_address sa ON sa.fk_soc = se.fk_object AND sa.type = 'livraison'
WHERE se.adressedelivraison IS NOT NULL
  AND se.adressedelivraison != ''
  AND sa.rowid IS NULL;  -- Ne pas dupliquer

-- 3. VÉRIFICATION
-- SELECT COUNT(*) as nb_facturation FROM llx_societe_address WHERE type = 'facturation';
-- SELECT COUNT(*) as nb_livraison FROM llx_societe_address WHERE type = 'livraison';
-- SELECT COUNT(*) as nb_boutique FROM llx_societe_address WHERE type = 'boutique';
