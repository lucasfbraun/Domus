# Owner ↔ Property is many-to-many, Contract reaches owners only through Property

**Status**: accepted

`properties.owner_id` (a nullable 1:N foreign key) was replaced by an `owner_property` pivot table (migration `2026_07_18_150000_create_owner_property_table.php`, which backfills existing rows before dropping the column) because a property can genuinely have more than one owner — co-ownership is a real scenario for this business, not a hypothetical.

`Contract` was never given its own link to `Owner`. When a contract needs owner data (e.g. the `{{proprietario_*}}` template variables), it reads `contract->property->owners` and joins every owner's fields with a comma (`ContractDocumentService::buildVariables()`). This means **every** contract on a co-owned property lists **all** of that property's owners as landlord — there's no way for one specific contract to name only a subset of the property's owners. That's a deliberate boundary, not an oversight: introducing per-contract owner selection would need a new `contract_owner` pivot and a redesign of how the template variables are computed.
