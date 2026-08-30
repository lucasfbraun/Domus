# Charge generation day is a single global setting, decoupled from each contract's due date

**Status**: accepted

Before this change, `ChargeScheduler::runMonthlyChargeSweep()` created each Contract's monthly Charge on a fixed schedule *relative to that Contract's own `due_day`*: always 5 days ahead of it (with a 3-day catch-up window after). That number was a hardcoded class constant, not configurable, and every Contract effectively had its own generation date derived from its own due date.

The request was to make "which day generates the charge" admin-configurable. Two shapes were considered: keep the relative "N days before due date" semantics but make N configurable (per Contract, or globally), or switch to a single fixed calendar day that generates that cycle's Charge for *every* Contract regardless of its own due date. We chose the latter, as a single system-wide setting (`BillingSetting`, a singleton row), because:

- It matches common real-estate billing practice: invoices/charges go out on one fixed day for the whole portfolio (e.g. "always the 1st"), independent of when each tenant's rent is actually due.
- It is simpler to reason about and to expose in the UI: one number, one settings page, instead of a per-contract field that would also need touching every contract form/request/factory.
- `BillingCycle::resolveBillingCycleDueDate()` already tolerates a generation day that falls before or after a given Contract's `due_day` — it rolls forward to next month's due date when today is more than 10 days past this month's, so decoupling the two is not a new edge case, just relying on math that already existed.

Consequence: a Contract whose `due_day` is earlier in the month than the configured generation day will have its Charge created on/after that day already close to or past due, rather than several days ahead of it. This is an accepted trade-off of a single global day; if that turns out to matter in practice, the fix is a per-contract override on top of this global default, not a redesign.

The generation-day check only gates the *automatic* monthly sweep (the scheduled job). The admin's manual "gerar cobrança" button (`ChargeScheduler::generateChargeForContract()`) is unaffected and can always be used on demand.
