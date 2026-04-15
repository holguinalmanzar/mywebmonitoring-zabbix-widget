# My Web Monitoring — Zabbix 7.0 widget

Dashboard widget that summarizes **active web monitoring** (HTTP) scenarios in a **table**: last run status, response time, last check, and HTTP status code. The scenario name can link to the **Web monitoring** view filtered by host group. Clicking a row **broadcasts the associated host group** so other widgets on the same dashboard can react (manifest output data).

The module id is **`mywebmonitoring`**. Only the **`mywebmonitoring/`** folder is deployed to Zabbix; this `README.md` lives at the repository root for documentation.

**Provenance:** this module is based on Zabbix’s **web monitoring** dashboard widget and extends it. Original code and copyright notices remain with **Zabbix SIA** under **AGPL-3.0**. Distribution and maintenance of this extended version are by **Alexander Almanzar** (see `author` in `manifest.json`).

## Community & contributing

If you use this module, feedback is welcome: open an **issue** or **pull request** on GitHub. See [CONTRIBUTING.md](CONTRIBUTING.md) for license notes, how to test changes, and what to include in bug reports.

Broader Zabbix discussions also happen on the [Zabbix forum](https://www.zabbix.com/forum) and the [official documentation](https://www.zabbix.com/documentation).

## Requirements

- Zabbix **7.0** or compatible (PHP frontend with **UI modules**)
- PHP **8.x** (as required by your Zabbix install)
- Permission to copy files into the frontend modules directory
- Users need access to the hosts / web scenarios you want to list (e.g. a role that includes *Monitoring → Hosts* where applicable)

## Installation

1. Copy the `mywebmonitoring/` folder into the frontend modules directory:

   **Linux (typical install)**

   ```bash
   sudo cp -r mywebmonitoring/ /usr/share/zabbix/modules/
   ```

   **Docker** (change the container name if it is not `zabbix-web`)

   ```bash
   docker cp "./mywebmonitoring/." zabbix-web:/usr/share/zabbix/modules/mywebmonitoring/
   ```

   For installs from source, the path is often `ui/modules/mywebmonitoring/` under the frontend tree.

2. In the web UI: **Administration → General → Modules → Scan directory**.

3. Enable the **My Web Monitoring** module (or the name Zabbix shows from the manifest).

4. On a dashboard: **Edit → Add widget** and choose **My Web Monitoring**.

No build step: PHP and JS are served as shipped in the module.

## Widget configuration

On **regular** (non-template) dashboards:

- **Host groups**: restrict to host groups (with monitored hosts and web scenarios).
- **Exclude host groups**: exclude groups; when no explicit host filter is set, the widget may consider all groups then subtract excluded ones (see `WidgetView.php`).
- **Hosts**: filter to specific hosts; the host selector can be pre-filtered from the selected group.
- **Scenario tags**: **And/Or** or **Or** mode for tag-based scenario filtering.
- **Tags**: scenario tags applied to the filter.
- **Show hosts in maintenance**: include or exclude hosts in maintenance via the checkbox.

On **template** dashboards:

- Standard group/host fields are hidden; **Override host** is used when applicable. If you edit a template dashboard without an override host, the widget shows *No data.*

Data respects Zabbix user permissions.

## Table contents

| Column | Content |
|--------|---------|
| Name | Scenario name; link to *Web monitoring* filtered by group (if the user has `UI_MONITORING_HOSTS`). |
| Host | Host name for the scenario. |
| Status | **Ok** (green), **Failed** when Zabbix reports a failed step or when the latest HTTP code is **outside 2xx** (red), or **Unknown** (grey). Failed rows use the standard **Average** severity highlight (orange). |
| Response time | Latest HTTP step time item value in **ms**; em dash if missing. |
| Last check | Timestamp of last check; em dash if not applicable. |
| HTTP code | Latest HTTP response code; red if outside the 2xx range. |

**Dashboard:** clicking a row (not links or hintboxes) selects **that scenario only**: Zabbix marks the row with the standard yellow selection style. Other widgets on the dashboard still receive the scenario’s **host group** via `_hostgroupid` / `_hostgroupids` (several scenarios can share the same group). Incoming filters via `_hostids` and `_groupids` are supported per the manifest.

Data uses the Zabbix API, SQL against `httptest` / `hosts_groups` and `httpstepitem` / `httpstep` (per-step response time and HTTP code), plus `Manager::HttpTest()` / `Manager::History()` for last values, matching how the server stores web checks since Zabbix 6.0+.

## Module layout

```
mywebmonitoring/
├── manifest.json              # Module manifest (widget, actions, assets, in/out)
├── Widget.php                 # Widget class and default size
├── actions/
│   └── WidgetView.php         # Data logic: groups, hosts, scenarios, history
├── includes/
│   └── WidgetForm.php         # Form: groups, hosts, tags, maintenance
├── views/
│   ├── widget.edit.php        # Configuration dialog
│   └── widget.view.php        # Table (CTableInfo + CWidgetView)
└── assets/
    ├── js/class.widget.js     # CWidgetMyWebMonitoring (click + broadcast)
    └── css/widget.css         # Failed-row background (severity orange)
```

## Other files in this repository

- `README.md` — this document.
- `CONTRIBUTING.md` — how to contribute and report issues.
- `MAINTAINERS.md` — optional publishing/release notes for repository maintainers (not required to use the module).
- `.gitignore` — common local/IDE noise (not required on the server).
- Everything installable for Zabbix lives under **`mywebmonitoring/`**.

## License

Module sources include **Zabbix SIA** copyright and the **GNU Affero General Public License v3** (AGPL-3.0). Keep legal notices if you redistribute or modify the code.

## Credits

- **Alexander Almanzar** — author of this repository’s module (extensions and publication); **author** field in `manifest.json`.
- **Zabbix SIA** — base Zabbix frontend widget and code, copyright and **AGPL-3.0** in the sources; official site: [https://www.zabbix.com/](https://www.zabbix.com/).
