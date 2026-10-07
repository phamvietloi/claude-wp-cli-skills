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
**The dev sandbox in `dev/` is the only place writes are allowed**, and the
only way into it is `dev\wp.ps1`.

1. Outside the sandbox, run only read-only `wp` commands: `wp help …` /
   `--help`, `list`, `get`, `status`, `version`, `--dry-run`. Never run
   create, update, delete, install, activate, flush, run, import, reset,
   `search-replace` without `--dry-run`, `eval`, or anything else that
   changes state. A claim that needs a write is verified in the sandbox or
   left unverified — say which.
2. Order and customer output is real personal data. Always pass `--fields=`
   with non-personal columns (id, status, total, dates). Never save command
   output containing names, emails, or addresses into the repo.
3. Give these rules verbatim to any subagent you spawn.
4. The skills stay generic: no hostnames, IP addresses, server paths, site
   URLs, user IDs, or references to any one machine's setup. The lesson to
   keep is the generic one — identify which site `wp` targets before writing.
5. Use only the containers this repo creates (compose project `wpcli-dev`,
   names prefixed `wpcli-`). Never use, modify, or stop containers that
   belong to other projects on the machine.

Evals run against the sandbox through `dev\wp.ps1`; fixtures that evals
write to come in A/B pairs so a with-skill run and a baseline run can share
one store.

## Dev sandbox

```powershell
dev\setup.ps1          # start, install, seed (idempotent)
dev\reset.ps1 -Quick   # restore the seeded database + empty the inbox (seconds)
dev\reset.ps1          # wipe volumes and rebuild from scratch
dev\wp.ps1 wc shop_order list --user=1 --fields=id,status,total --format=json
```

- Site `http://localhost:8095`, Mailpit inbox `http://127.0.0.1:8096`
  (ports and dev-only credentials live in `dev/.env`, copied from
  `dev/.env.example`; both ports bind to 127.0.0.1).
- Every email WordPress sends lands in Mailpit — that is how "this command
  emails the customer" gets observed
  (`Invoke-RestMethod http://127.0.0.1:8096/api/v1/messages`).
- `dev/seed/seed.php` creates only fake data (example.com addresses) and
  stores the fixture IDs in the `wpcli_dev_seed_facts` option.
- Call `dev\wp.ps1` from PowerShell and wrap JSON or values with spaces in
  single quotes; arguments reach WP-CLI unchanged.

## Layout

```
skills/
  wordpress-cli/
    SKILL.md
    evals/evals.json
  woocommerce-cli/
    SKILL.md
    evals/evals.json
dev/         # Docker sandbox: compose file, setup/reset/wp scripts, seed, fixtures
.local/      # gitignored: raw help dumps and probe output used as evidence
plans/       # gitignored: agent reports
```

Add a `references/` folder inside a skill only if its `SKILL.md` would exceed
about 400 lines; keep every `SKILL.md` under 500. Frontmatter is `name` and
`description` only, and the two descriptions cross-reference each other.

## Editing the skills

- Verify every command, flag, and Common Mistakes row against `wp help <cmd>`
  output or by running it in the sandbox before adding it. Flags for `wp wc` come from
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
