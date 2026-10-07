# claude-wp-cli-skills

Private Claude Code skills for running WordPress and WooCommerce from the
terminal.

| Skill | Covers |
|-------|--------|
| [`wordpress-cli`](skills/wordpress-cli/SKILL.md) | WP-CLI (`wp`): core, plugins, themes, posts, users, options, cron, cache, database, search-replace, site recovery |
| [`woocommerce-cli`](skills/woocommerce-cli/SKILL.md) | `wp wc`: orders, notes, refunds, products, variations, customers, coupons, HPOS, maintenance tools |

Each skill gives an agent the command grammar, the output-format contract
(`--format=json`, `--fields=`), a table of common mistakes, and a safety
section that separates read-only commands from writes that need explicit
approval — a store write can email a customer or move money.

## Install

Windows (directory junctions, no admin rights needed):

```powershell
$repo = (Get-Location).Path
foreach ($s in 'wordpress-cli', 'woocommerce-cli') {
  New-Item -ItemType Junction -Path "$env:USERPROFILE\.claude\skills\$s" -Target "$repo\skills\$s"
}
```

macOS / Linux:

```bash
ln -s "$PWD/skills/wordpress-cli"   ~/.claude/skills/wordpress-cli
ln -s "$PWD/skills/woocommerce-cli" ~/.claude/skills/woocommerce-cli
```

Start a new Claude Code session; both skills appear in the skill list.

## Dev sandbox

`dev/` holds a throwaway WordPress + WooCommerce store in Docker. It is the
only place where commands that write may be run.

```powershell
dev\setup.ps1          # start, install WordPress + WooCommerce, enable HPOS, seed fake data
dev\wp.ps1 wc product list --user=1 --fields=id,sku,regular_price --format=json
dev\reset.ps1 -Quick   # back to the seeded state in a few seconds
dev\reset.ps1          # wipe volumes and rebuild
```

| What | Where |
|------|-------|
| Site | http://localhost:8095 (admin login in `dev/.env`) |
| Captured email (Mailpit) | http://127.0.0.1:8096 |
| Containers | `wpcli-wp`, `wpcli-cli`, `wpcli-db`, `wpcli-mail` (compose project `wpcli-dev`) |

Requires Docker Desktop and PowerShell 7. Ports and dev-only credentials come
from `dev/.env`, which `setup.ps1` creates from `dev/.env.example`.

Seeded data, all fake: 7 simple products with SKUs, 2 variable T-shirts with
3 size variations each, 6 customers (`@example.com`), 32 orders across every
status dated August–October 2026, 1 coupon, a handful of users, posts, and
pages, and two inactive fixture plugins used to stage a "broken plugin".
Fixtures that tests write to exist as A/B pairs.

### Versions the skills were verified against

| Component | Version |
|-----------|---------|
| WP-CLI | 2.12.0 |
| WordPress | 7.1.2 |
| WooCommerce | 11.1.2 (HPOS enabled) |
| PHP | 8.3 |
| MariaDB | 11 |

Behaviour stated in the skills (which commands send email, what `delete`
does per resource, how JSON flags merge, and so on) was observed on this
stack. Re-run the checks after a major WooCommerce upgrade.

## Evals

`skills/<name>/evals/evals.json` holds three test prompts per skill in the
skill-creator schema. They are executed against the dev sandbox through
`dev\wp.ps1`; run `dev\reset.ps1 -Quick` first so every run starts from the
same data. Results go to `skills/<name>-workspace/` (gitignored).

## Development

See [CLAUDE.md](CLAUDE.md) for the safety rules and layout. In short: verify
against `wp help` or the sandbox, write only inside the sandbox, keep
personal data and site-specific details out of the repo.
