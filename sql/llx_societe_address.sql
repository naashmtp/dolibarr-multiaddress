-- ============================================================================
-- Module MultiAddress - Gestion multi-adresses pour tiers
-- Copyright (C) 2025
-- ============================================================================

-- Modification de la table societe_address pour ajouter le typage
ALTER TABLE llx_societe_address
ADD COLUMN type VARCHAR(20) DEFAULT 'facturation' NOT NULL AFTER fk_soc,
ADD COLUMN is_default TINYINT(1) DEFAULT 0 AFTER type,
ADD COLUMN latitude DECIMAL(10,8) NULL AFTER fk_pays,
ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude,
ADD COLUMN visible_vogliomap TINYINT(1) DEFAULT 0 AFTER longitude,
ADD COLUMN status TINYINT(1) DEFAULT 1 AFTER visible_vogliomap;

-- Index pour performances
ALTER TABLE llx_societe_address ADD INDEX idx_type (type);
ALTER TABLE llx_societe_address ADD INDEX idx_fk_soc_type (fk_soc, type);
ALTER TABLE llx_societe_address ADD INDEX idx_vogliomap (visible_vogliomap);

-- Contrainte pour s'assurer qu'on ne peut avoir qu'une seule adresse par défaut par type par société
-- ALTER TABLE llx_societe_address ADD UNIQUE KEY unique_default_per_type (fk_soc, type, is_default);
