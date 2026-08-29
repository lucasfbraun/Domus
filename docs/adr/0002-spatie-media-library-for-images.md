# Spatie Media Library required for image uploads, with a filesystem exception for generated PDFs

**Status**: accepted

Every model that stores an image (currently `Property` cover photos) must use `spatie/laravel-medialibrary` — implementing `HasMedia`, and registering a WebP conversion via `RegistersOptimizedWebpConversions` — instead of storing the file with raw `Storage::put()`/`$file->store()` and a plain path column. The goal is a single, consistent optimization pipeline: every uploaded image automatically gets an optimized WebP conversion, rather than each feature reinventing its own ad-hoc storage and never optimizing the asset.

This does **not** extend to non-image documents. Generated/signed contract PDFs (`Contract::generated_document_path`, `signed_document_path`, `owner_signed_document_path`) and rateio invoice files intentionally stay on plain filesystem storage — they aren't a "media gallery" concept, don't need image conversions, and Media Library's collection model would add overhead without benefit for a private, single-purpose binary file tied 1:1 to a contract.

Contract inspection photos (`ContractInspectionPhoto`) and occurrence photos (`ContractOccurrencePhoto`) currently also use raw filesystem storage with a hand-rolled `storage_path`/`content_type` column pair, which is a deviation from this rule rather than a deliberate exception — they're images, so the rule says they should be on Media Library. Left as-is for now; worth migrating if that code is touched again.
