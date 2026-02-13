# MultiAddress - Dolibarr Module

Multi-address management for third parties. Adds support for billing, shipping, and store addresses per customer.

## Features

- 3 address types per third party: billing, shipping, store (boutique)
- Default address per type
- GPS coordinates (lat/lng) per address
- Map visibility toggle for store addresses
- GeoJSON/JSON API export for map integration
- Automatic fallback to main address when no specific address exists

## Requirements

- Dolibarr 19.x+
- Table `llx_societe_address` (created by install script)

## Installation

1. Copy to `htdocs/custom/multiaddress/`
2. Run SQL scripts from `sql/` directory
3. Enable module in Admin > Modules

## API

Export store addresses as JSON or GeoJSON:

```
GET /custom/multiaddress/api/boutiques.php
GET /custom/multiaddress/api/boutiques.php?format=geojson&visible_only=1
```

## Structure

```
multiaddress/
  address.php                          - Address management UI
  admin/setup.php                      - Module config
  api/boutiques.php                    - Store export API
  core/classes/address.class.php       - Address CRUD class
  core/modules/modMultiAddress.class.php
  lib/multiaddress.lib.php
  sql/                                 - Install/update scripts
```

## License

GPL v3
