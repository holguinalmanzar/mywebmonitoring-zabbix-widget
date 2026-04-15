<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Modules\Mywebmonitoring\Actions;

use API,
	CApiTagHelper,
	CArrayHelper,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CRoleHelper,
	CSettingsHelper,
	Manager;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			],
			'allowed_ui_hosts' => $this->checkAccess(CRoleHelper::UI_MONITORING_HOSTS)
		];

		// Editing template dashboard?
		if ($this->isTemplateDashboard() && !$this->fields_values['override_hostid']) {
			$data['error'] = _('No data.');
		}
		else {
			$filter_groupids = !$this->isTemplateDashboard() && $this->fields_values['groupids']
				? getSubGroups($this->fields_values['groupids'])
				: null;

			if ($this->isTemplateDashboard()) {
				$filter_hostids = $this->fields_values['override_hostid'];
			}
			else {
				$filter_hostids = $this->fields_values['hostids'] ?: null;
			}

			$filter_maintenance = $this->fields_values['maintenance'] == 0 ? 0 : null;

			if (!$this->isTemplateDashboard() && $this->fields_values['exclude_groupids']) {
				$exclude_groupids = getSubGroups($this->fields_values['exclude_groupids']);

				if ($filter_hostids === null) {

					// Get all groups if no selected groups defined.
					if ($filter_groupids === null) {
						$filter_groupids = array_keys(API::HostGroup()->get([
							'output' => [],
							'with_hosts' => true,
							'preservekeys' => true
						]));
					}

					$filter_groupids = array_diff($filter_groupids, $exclude_groupids);

					// Get available hosts.
					$filter_hostids = array_keys(API::Host()->get([
						'output' => [],
						'groupids' => $filter_groupids,
						'preservekeys' => true
					]));
				}

				$exclude_hostids = array_keys(API::Host()->get([
					'output' => [],
					'groupids' => $exclude_groupids,
					'preservekeys' => true
				]));

				$filter_hostids = array_diff($filter_hostids, $exclude_hostids);
			}

			$groups = API::HostGroup()->get([
				'output' => ['groupid', 'name'],
				'groupids' => $filter_groupids,
				'hostids' => $filter_hostids,
				'with_monitored_hosts' => true,
				'with_monitored_httptests' => true,
				'preservekeys' => true
			]);

			CArrayHelper::sort($groups, ['name']);

			$groupids = array_keys($groups);

			$hosts = API::Host()->get([
				'output' => ['hostid', 'name'],
				'groupids' => $groupids,
				'hostids' => $filter_hostids,
				'filter' => ['maintenance_status' => $filter_maintenance],
				'monitored_hosts' => true,
				'preservekeys' => true
			]);

			// Fetch links between HTTP tests and host groups (one row per test/group pair).
			$where_tags = (array_key_exists('tags', $this->fields_values) && $this->fields_values['tags'])
				? CApiTagHelper::addWhereCondition($this->fields_values['tags'], $this->fields_values['evaltype'], 'ht',
					'httptest_tag', 'httptestid'
				)
				: '';

			$result = DbFetchArray(DBselect(
				'SELECT ht.httptestid,ht.name,ht.hostid,hg.groupid' .
				' FROM httptest ht,hosts_groups hg' .
				' WHERE ht.hostid=hg.hostid' .
				' AND ' . dbConditionInt('hg.hostid', array_keys($hosts)) .
				' AND ' . dbConditionInt('hg.groupid', $groupids) .
				' AND ht.status=' . HTTPTEST_STATUS_ACTIVE .
				(($where_tags !== '') ? ' AND ' . $where_tags : '')
			));

			// Index the result by httptestid, keeping the first groupid for each test.
			$tests_by_id = [];
			foreach ($result as $row) {
				if (!array_key_exists($row['httptestid'], $tests_by_id)) {
					$tests_by_id[$row['httptestid']] = [
						'httptestid' => $row['httptestid'],
						'name'       => $row['name'],
						'hostid'     => $row['hostid'],
						'groupid'    => $row['groupid'],
						'host_name'  => array_key_exists($row['hostid'], $hosts) ? $hosts[$row['hostid']]['name'] : '',
					];
				}
			}

			$httptestids = array_keys($tests_by_id);

			// Fetch HTTP test execution data (lastcheck, lastfailedstep, error).
			$httptest_data = $httptestids ? Manager::HttpTest()->getLastData($httptestids) : [];

			// Step-level RSPCODE/TIME items live in httpstepitem → httpstep (Zabbix 6.0+).
			// httptestitem only links scenario-level items (LASTSTEP, LASTERROR, scenario IN), not per-step time/code.
			// Several steps ⇒ multiple itemids per scenario per type; pick the value with the newest history clock.
			$httptest_items_by_type = [];
			$all_history_items = [];

			if ($httptestids) {
				$item_rows = DbFetchArray(DBselect(
					'SELECT hsi.itemid,hs.httptestid,hsi.type,i.value_type' .
					' FROM httpstepitem hsi,httpstep hs,items i' .
					' WHERE hsi.httpstepid=hs.httpstepid' .
					' AND hsi.itemid=i.itemid' .
					' AND ' . dbConditionInt('hs.httptestid', $httptestids) .
					' AND ' . dbConditionInt('hsi.type', [HTTPSTEP_ITEM_TYPE_TIME, HTTPSTEP_ITEM_TYPE_RSPCODE])
				));

				foreach ($item_rows as $item_row) {
					$hid = $item_row['httptestid'];
					$typ = (int) $item_row['type'];
					$iid = $item_row['itemid'];

					if (!array_key_exists($hid, $httptest_items_by_type)) {
						$httptest_items_by_type[$hid] = [];
					}
					if (!array_key_exists($typ, $httptest_items_by_type[$hid])) {
						$httptest_items_by_type[$hid][$typ] = [];
					}
					$httptest_items_by_type[$hid][$typ][] = $iid;

					$all_history_items[$iid] = [
						'itemid'     => $iid,
						'value_type' => $item_row['value_type']
					];
				}
			}

			// Fetch last history values using the same Manager::History approach Zabbix uses internally.
			$item_history = [];
			if ($all_history_items) {
				$item_history = Manager::History()->getLastValues(
					array_values($all_history_items),
					1,
					timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD))
				);
			}

			$pick_latest_item_value = static function (array $candidate_itemids, array $item_history) {
				$best_clock = null;
				$best = null;

				foreach ($candidate_itemids as $iid) {
					$row = null;
					foreach ([$iid, (string) $iid, (int) $iid] as $key) {
						if (isset($item_history[$key][0])) {
							$row = $item_history[$key][0];
							break;
						}
					}
					if ($row === null) {
						continue;
					}

					$clock = (int) ($row['clock'] ?? 0);

					if ($best_clock === null || $clock >= $best_clock) {
						$best_clock = $clock;
						$best = $row['value'];
					}
				}

				return $best;
			};

			// Build the final tests array with all display data.
			$tests = [];
			foreach ($tests_by_id as $httptestid => $test) {
				$last = array_key_exists($httptestid, $httptest_data) ? $httptest_data[$httptestid] : null;

				if ($last !== null && $last['lastfailedstep'] !== null) {
					$status = ($last['lastfailedstep'] != 0) ? 'failed' : 'ok';
				}
				else {
					$status = 'unknown';
				}

				$response_time = null;
				if (array_key_exists($httptestid, $httptest_items_by_type)
						&& array_key_exists(HTTPSTEP_ITEM_TYPE_TIME, $httptest_items_by_type[$httptestid])) {
					$raw = $pick_latest_item_value(
						$httptest_items_by_type[$httptestid][HTTPSTEP_ITEM_TYPE_TIME],
						$item_history
					);
					if ($raw !== null && is_numeric($raw)) {
						$response_time = (float) $raw;
					}
				}

				$http_code = null;
				if (array_key_exists($httptestid, $httptest_items_by_type)
						&& array_key_exists(HTTPSTEP_ITEM_TYPE_RSPCODE, $httptest_items_by_type[$httptestid])) {
					$raw = $pick_latest_item_value(
						$httptest_items_by_type[$httptestid][HTTPSTEP_ITEM_TYPE_RSPCODE],
						$item_history
					);
					if ($raw !== null && is_numeric($raw)) {
						$http_code = (int) $raw;
					}
				}

				// If the HTTP code is non-2xx, override status to 'failed'
				// regardless of lastfailedstep (e.g. scenario has no required-code check).
				if ($status === 'ok' && $http_code !== null && ($http_code < 200 || $http_code >= 300)) {
					$status = 'failed';
				}

				$tests[$httptestid] = $test + [
					'status'         => $status,
					'lastcheck'      => ($last !== null) ? ($last['lastcheck'] ?? null) : null,
					'lastfailedstep' => ($last !== null) ? ($last['lastfailedstep'] ?? null) : null,
					'error'          => ($last !== null) ? ($last['error'] ?? null) : null,
					'response_time'  => $response_time,
					'http_code'      => $http_code
				];
			}

			// Sort by host name then scenario name.
			CArrayHelper::sort($tests, ['host_name', 'name']);

			$data += [
				'error'  => null,
				'tests'  => $tests,
				'groups' => $groups
			];
		}

		$this->setResponse(new CControllerResponseData($data));
	}
}
