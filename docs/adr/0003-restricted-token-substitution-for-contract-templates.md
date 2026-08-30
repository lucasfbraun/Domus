# Contract templates use a closed `{{key}}` token catalog, not a real templating engine

**Status**: accepted

`ContractTemplate::content` is admin-authored HTML that gets merged with per-contract data and turned into a PDF. Rendering it through a real templating engine (Blade, Twig) was rejected: those engines can execute arbitrary directives/expressions, and template content here is user input from the admin UI, not a trusted developer-authored view — a full engine would turn "edit a contract template" into an arbitrary code execution surface.

Instead, `ContractTemplateVariables` defines a fixed, hardcoded catalog of allowed keys (`inquilino_nome`, `valor_aluguel`, etc.). `sanitizeHtml()` strips everything to a small tag allow-list and only preserves `{{key}}`/`data-template-variable` tokens whose key is in that catalog — an unrecognized key is neutralized back to inert literal text. `ContractDocumentService::renderTemplate()` then does plain string substitution against a variables array built server-side from the `Contract` model, never evaluating anything from the template content itself.

The trade-off: adding a new variable requires a code change (new catalog entry + a value provider in `buildVariables()`) instead of just referencing a new field from the template — there's no way for an admin to introduce a new merge field on their own. That's accepted as the cost of not having a code-injection surface in admin-editable content.

**Addendum**: `buildVariables()` formats CPF/CNPJ and phone values (`BrazilianDocument::format()`, `BrazilianPhone::format()`) at substitution time rather than storing them formatted. The database only ever holds digits-only documents/phones (see `PreparesBrazilianFields`) — formatting is applied wherever a value is *displayed* (here, and in the payment receipt), never stored, so there's exactly one place per surface that has to remember to format and no risk of a formatted and an unformatted copy of the same value drifting apart in the database.
