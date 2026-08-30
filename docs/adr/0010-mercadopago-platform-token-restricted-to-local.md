# Mercado Pago platform-token fallback only works outside production

**Status**: accepted

In production, every Receiver connects their own Mercado Pago account via OAuth (`ReceiverController::connectMercadoPago`), and `MercadoPagoService` uses that receiver's own `mp_access_token` to create Pix orders on their behalf. Locally, setting up OAuth for every developer/test receiver is friction nobody needs — `MP_ACCESS_TOKEN` in `.env` lets the whole app fall back to one shared token instead.

`MercadoPagoService::allowsPlatformTokenFallback()` gates that fallback on `config('app.env')` being `local` or `testing` — nothing else. This is deliberate, not an oversight:

- The platform token belongs to whoever owns the Mercado Pago application credentials configured in `.env` — using it as a stand-in for an unconnected receiver would mean **that Pix charge is actually collected into the platform owner's account**, not the receiver's. That's fine as a local/CI convenience (nobody is really getting paid), catastrophic in any real deployment.
- The check is a plain string comparison against `app.env`, not a dedicated feature flag. This is intentionally the simplest possible gate — one env value the deploy process already sets correctly (Dokku's environment doesn't include `APP_ENV=local` or `=testing`) — rather than a second switch someone has to remember to also set correctly. The trade-off: introducing a new environment name (e.g. a `staging` that isn't `local`/`testing`) is safe by default (falls back to requiring real OAuth, the secure direction), but anyone who *wants* the shortcut in a new non-production environment has to know to add that name to the `in_array()` check rather than just flip a flag.
- `assertOrdersApiAccessToken()` additionally rejects any `TEST-` prefixed token even when the fallback is allowed — the Orders API doesn't accept sandbox tokens at all, so this isn't a sandbox/production switch, it's strictly "whose real production token gets used."

If a genuine staging environment is added later, extend the `in_array()` in `allowsPlatformTokenFallback()` deliberately — don't switch it to a boolean config flag that could be left on by accident in production.
