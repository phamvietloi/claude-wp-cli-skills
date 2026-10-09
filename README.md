# claude-wp-cli-skills

Two [Claude Code](https://claude.com/claude-code) skills for running
WordPress and WooCommerce from the terminal.

| Skill | Covers |
|-------|--------|
| [`wordpress-cli`](skills/wordpress-cli/SKILL.md) | WP-CLI (`wp`): core, plugins, themes, posts, users, options, cron, cache, database, search-replace, site recovery |
| [`woocommerce-cli`](skills/woocommerce-cli/SKILL.md) | `wp wc`: orders, notes, refunds, products, variations, customers, coupons, HPOS, maintenance tools |

Each skill gives an agent the command grammar, the output-format contract
(`--format=json`, `--fields=`), a table of common mistakes, and a safety
section that separates read-only commands from writes that need explicit
approval — a store write can email a customer or move money.

They are for developers, site maintainers, and store operators who let a
coding agent run `wp` for them — over SSH, in a container, or in a script —
and want it to ask before it does something that cannot be taken back. The
skills are plain Markdown and do not install or run anything themselves;
WP-CLI must already be available wherever the agent works.

## Install

Pick one method. Using both loads every skill twice.

### As a Claude Code plugin

Inside Claude Code:

```
/plugin marketplace add phamvietloi/claude-wp-cli-skills
/plugin install wp-cli-skills@claude-wp-cli-skills
```

The skills then appear as `wp-cli-skills:wordpress-cli` and
`wp-cli-skills:woocommerce-cli`. The same from a shell:
`claude plugin marketplace add phamvietloi/claude-wp-cli-skills`, then
`claude plugin install wp-cli-skills@claude-wp-cli-skills`.

### From a clone

Link the two skill folders into your personal skills directory, so a
`git pull` updates them in place.

Windows (directory junctions, no admin rights needed), from the repo root:

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

## What to expect

The skills were evaluated twice against the dev sandbox described below:
12 runs, then 36 runs, each task done both with and without the skill. A
current Claude model reached the same final outcome either way in every
run. The differences that were measured:

- `woocommerce-cli`: fewer failed commands along the way, and faster runs.
- `wordpress-cli`: no measurable benefit, at a cost of roughly 7–8k extra
  tokens each time the skill is used.

The skills are meant to matter for writes that cannot be undone — an email
that reaches a customer, a refund sent through a payment gateway. The evals
did not measure that, so treat it as the intent of the skills rather than a
demonstrated result.

## Limitations

- Behaviour was verified on one version set only (table below). Which
  commands send email, what `delete` does per resource, how JSON flags
  merge, and similar claims were observed on that stack.
- `wp wc` flags are generated from the installed WooCommerce version and
  can differ from yours and from the current REST API docs. Check
  `wp help wc <resource> <verb>` on the target site.
- The dev scripts in `dev/` are PowerShell 7 and were only run with Docker
  Desktop on Windows. The skills themselves are not tied to any OS.

### Versions the skills were verified against

| Component | Version |
|-----------|---------|
| WP-CLI | 2.12.0 |
| WordPress | 7.1.2 |
| WooCommerce | 11.1.2 (HPOS enabled) |
| PHP | 8.3 |
| MariaDB | 11 |

Re-run the checks after a major WooCommerce upgrade.

## Dev sandbox

`dev/` holds a throwaway WordPress + WooCommerce store in Docker, used to
check what a command really does before the skills say so. You only need it
to work on the skills, not to use them.

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
3 size variations each, 6 customers (`@example.com`), 156 orders across every
status dated May–October 2026 (more than one page of completed orders),
1 coupon, a handful of users, posts, and pages, and two inactive fixture
plugins used to stage a "broken plugin". Fixtures that tests write to exist
as A/B pairs.

## Evals

`skills/<name>/evals/evals.json` holds three test prompts per skill in the
skill-creator schema. They are executed against the dev sandbox through
`dev\wp.ps1`; run `dev\reset.ps1 -Quick` first so every run starts from the
same data. Results go to `skills/<name>-workspace/` (gitignored).

## Contributing

See [CLAUDE.md](CLAUDE.md) for the safety rules and layout. In short: verify
every claim against `wp help` or the sandbox, write only inside the sandbox,
and keep personal data and site-specific details out of the repo.

## License and disclaimer

[MIT](LICENSE).

This is an independent project. It is not affiliated with or endorsed by
WordPress, WooCommerce, Automattic, or WP-CLI; those names are trademarks of
their respective owners.
