# Contributing

Thanks for helping improve this Zabbix UI module.

## License and attribution

- The module is derived from Zabbix frontend code and remains under **AGPL-3.0**.
- **Do not remove** existing copyright lines from Zabbix SIA in source files.
- For non-trivial changes, you may add your own copyright line for your contributions (see [GNU recommendations](https://www.gnu.org/licenses/gpl-howto.html) for derivative works).

## Before you open a pull request

1. **Test on a real Zabbix server** (or container): copy `mywebmonitoring/` into the frontend `modules/` directory, run **Administration → General → Modules → Scan directory**, enable the module, add the widget to a dashboard with real web scenarios.
2. **Keep changes focused**: one feature or fix per PR makes review easier.
3. **Mention your environment** in the PR or issue: Zabbix version (e.g. 7.0.x), PHP version, and whether you use packages or Docker.

## Reporting issues

Include:

- Zabbix version and how the frontend is installed (Docker image name, OS packages, etc.).
- What you expected vs what happened (screenshots help).
- If the problem is data-related: whether native **Monitoring → Web** shows the same scenarios correctly.

## Code style

- Match the surrounding PHP/JS style in the repo (same naming and patterns as the Zabbix widget you extended).
- Avoid unrelated refactors in the same change as a bugfix.

Questions and suggestions are welcome via **GitHub Issues** in this repository.
