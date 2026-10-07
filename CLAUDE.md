# claude-wp-cli-skills

Two private Claude Code skills that teach an agent to operate WordPress and
WooCommerce from the command line:

- `wordpress-cli` — WP-CLI (`wp`) for core, plugins, themes, content, users,
  options, cron, cache, and the database.
- `woocommerce-cli` — `wp wc` for orders, refunds, products, customers,
  coupons, and store maintenance.

Both follow the skill-creator anatomy and share one section skeleton: Agent
Protocol, Trust boundary, Grammar, Flags/Resources, Common Mistakes, Safety,
Common Patterns.

## Safety rules for working in this repo

Assume `wp` on a developer machine may be wired to a live production store.

1. Run only read-only `wp` commands while developing or verifying the skills:
   `wp help …` / `--help`, `list`, `get`, `status`, `version`, `--dry-run`.
   Never run create, update, delete, install, activate, flush, run, import,
   reset, `search-replace` without `--dry-run`, `eval`, or anything else that
   changes state. If verifying a claim would need a write, leave it
   unverified and say so.
2. Order and customer output is real personal data. Always pass `--fields=`
   with non-personal columns (id, status, total, dates). Never save command
   output containing names, emails, or addresses into the repo.
3. Give rules 1–2 verbatim to any subagent you spawn.
4. The skills stay generic: no hostnames, IP addresses, server paths, site
   URLs, user IDs, or references to any one machine's setup. The lesson to
   keep is the generic one — identify which site `wp` targets before writing.

Evals must be answerable without executing writes: prompts ask for the exact
commands and an explanation as a written answer.

## Layout

```
skills/
  wordpress-cli/
    SKILL.md
    evals/evals.json
  woocommerce-cli/
    SKILL.md
    evals/evals.json
.local/      # gitignored: raw help dumps and probe output used as evidence
plans/       # gitignored: agent reports
```

Add a `references/` folder inside a skill only if its `SKILL.md` would exceed
about 400 lines; keep every `SKILL.md` under 500. Frontmatter is `name` and
`description` only, and the two descriptions cross-reference each other.

## Editing the skills

- Verify every command, flag, and Common Mistakes row against `wp help <cmd>`
  output or a read-only probe before adding it. Flags for `wp wc` come from
  the installed WooCommerce version and can differ from the current REST docs.
- Explain why a rule exists rather than stacking MUSTs.

## Install (Windows, no admin)

Directory junctions make the repo the live copy of each skill:

```powershell
$repo = 'C:\path\to\claude-wp-cli-skills'
foreach ($s in 'wordpress-cli', 'woocommerce-cli') {
  New-Item -ItemType Junction -Path "$env:USERPROFILE\.claude\skills\$s" -Target "$repo\skills\$s"
}
```

On macOS/Linux: `ln -s "$PWD/skills/wordpress-cli" ~/.claude/skills/wordpress-cli`
(and the same for `woocommerce-cli`).
