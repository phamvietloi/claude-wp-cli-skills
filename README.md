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

## Evals

`skills/<name>/evals/evals.json` holds three test prompts per skill in the
skill-creator schema. Every prompt asks for a written answer (exact commands
plus explanation), so the evals can be run without touching a real site.

## Development

See [CLAUDE.md](CLAUDE.md) for the safety rules and layout. In short: verify
against `wp help`, run nothing that writes, keep personal data and
site-specific details out of the repo.
