# Maintainer notes (not end-user documentation)

These snippets are for publishing and tagging the repository. They are intentionally **not** in `README.md`, which is aimed at users of the module.

## GitHub repository “About” description

```
Zabbix 7.x UI module: dashboard widget listing web scenarios with HTTP code, response time, last check, status, and host-group broadcast. AGPL-3.0.
```

## Suggested topics

`zabbix` `zabbix-widget` `web-monitoring` `dashboard` `php` `monitoring` `agpl-3` `zabbix-module`

## First release (example: v1.0.0)

- **Tag:** `v1.0.0`
- **Title:** `v1.0.0 — initial community release`

**Release notes body:**

```markdown
First tagged release of the **My Web Monitoring** dashboard module (`mywebmonitoring`).

**Highlights**
- Table of active web scenarios: name, host, status, response time, last check, HTTP code
- Per-step metrics via `httpstepitem` / `httpstep` (Zabbix 6.0+ storage model)
- Status reflects failed steps and non-2xx HTTP codes; failed rows use Average-severity orange highlight
- Host group broadcast on row click for linked dashboard widgets
- Optional filters: host groups, exclude groups, hosts, scenario tags, maintenance

**Install** — copy `mywebmonitoring/` into the Zabbix frontend `modules/` directory, scan modules in the UI, enable the module, add the widget to a dashboard. See README.md.

**License** — AGPL-3.0; includes Zabbix SIA copyright on derived frontend code (see repository files).
```

**Git commands (after pushing `main`):**

```bash
git tag -a v1.0.0 -m "v1.0.0 — initial community release"
git push origin main
git push origin v1.0.0
```

Then create the release on GitHub from tag `v1.0.0` and paste the release notes above.
