# Blue as the only brand primary color

**Status**: accepted

The product previously shipped with a teal/green primary palette. It was replaced with a fixed blue palette (`--primary: hsl(221 83% 53%)`) by explicit product direction, and the old hues (`hsl(150-175)` range, e.g. `hsl(175 72% 28%)`, `hsl(160 18% 97%)`) were declared forbidden rather than just discouraged. Green is still allowed, but only for semantic success states (toasts, status badges) — never for brand chrome (buttons, links, sidebar).

This is easy to accidentally revert: the old palette still compiles fine, and a well-meaning contributor or an AI agent restoring "the previous look" from an old screenshot or branch could reintroduce it without knowing it was explicitly banned. That's why the rule is machine-enforced by `tests/Feature/ThemeColorsTest.php` and repeated in `CLAUDE.md`/`AGENTS.md`/`.cursor/rules/brand-colors.mdc` — the redundancy is intentional, not an oversight.
