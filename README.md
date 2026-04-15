# My Web Monitoring — Zabbix 7.0 widget

Dashboard widget that summarizes **active web monitoring** (HTTP) scenarios in a **table**: last run status, response time, last check, and HTTP status code. The scenario name can link to the **Web monitoring** view filtered by host group. Clicking a row **broadcasts the associated host group** so other widgets on the same dashboard can react (manifest output data).

The module id is **`mywebmonitoring`**. Only the **`mywebmonitoring/`** folder is deployed to Zabbix; this `README.md` lives at the repository root for documentation.

**Provenance:** this module is based on Zabbix’s **web monitoring** dashboard widget and extends it. Original code and copyright notices remain with **Zabbix SIA** under **AGPL-3.0**. Distribution and maintenance of this extended version are by **Alexander Almanzar** (see `author` in `manifest.json`).

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
| Status | **Ok** (green), **Failed** with failed step when applicable (red), or **Unknown** (grey) from the last run. |
| Response time | Latest HTTP step time item value in **ms**; em dash if missing. |
| Last check | Timestamp of last check; em dash if not applicable. |
| HTTP code | Latest HTTP response code; red if outside the 2xx range. |

**Dashboard:** clicking a row (not links or hintboxes) highlights the row and the widget **broadcasts** `_hostgroupid` / `_hostgroupids` for other widgets that consume that data. It can accept incoming filters via `_hostids` and `_groupids` per the manifest.

Data uses the Zabbix API, queries against `httptest` / `httptestitem`, and `Manager::HttpTest()` / `Manager::History()` to align with Zabbix’s internal behaviour.

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
    └── js/class.widget.js     # CWidgetMyWebMonitoring (click + broadcast)
```

## Other files in this repository

- `README.md` (repository root) — this document. There are no other reference artefacts; everything installable lives under `mywebmonitoring/`.

## License

Module sources include **Zabbix SIA** copyright and the **GNU Affero General Public License v3** (AGPL-3.0). Keep legal notices if you redistribute or modify the code.

## Credits

- **Alexander Almanzar** — author of this repository’s module (extensions and publication); **author** field in `manifest.json`.
- **Zabbix SIA** — base Zabbix frontend widget and code, copyright and **AGPL-3.0** in the sources; official site: [https://www.zabbix.com/](https://www.zabbix.com/).
