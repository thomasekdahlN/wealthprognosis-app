# Tax Types Synchronization

- `tax_types.json` is conceptually produced from distinct keys across the country/year JSONs.
- Seeder `TaxTypesFromConfigSeeder` reads `tax_types.json` and seeds the `tax_types` table.
- Prefer `TaxTypesFromConfigSeeder` over the legacy `TaxTypeSeeder`.

