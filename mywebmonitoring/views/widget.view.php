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


/**
 * Web monitoring widget view.
 *
 * @var CView $this
 * @var array $data
 */

$table = (new CTableInfo())->addClass('mywebmonitoring-widget-table');

if ($data['error'] !== null) {
	$table->setNoDataMessage($data['error']);
}
else {
	$table
		->setHeader([
			_x('Name', 'compact table header'),
			_x('Host', 'compact table header'),
			_x('Status', 'compact table header'),
			_x('Response time', 'compact table header'),
			_x('Last check', 'compact table header'),
			_x('HTTP code', 'compact table header')
		])
		->setHeadingColumn(0);

	$web_url = $data['allowed_ui_hosts']
		? (new CUrl('zabbix.php'))->setArgument('action', 'web.view')->setArgument('filter_set', '1')
		: null;

	foreach ($data['tests'] as $test) {
		// Name cell — clickable label; JS shows action menu (Web monitoring / Visit site).
		$webmon_url_attr = '';
		if ($web_url !== null) {
			$web_url->setArgument('filter_groupids', [$test['groupid']]);
			$webmon_url_attr = $web_url->getUrl();
		}

		$site_url_attr = array_key_exists('site_url', $test) ? $test['site_url'] : '';

		$name_cell = (new CSpan($test['name']))->addClass('mywebmon-name-btn');

		// Status cell.
		switch ($test['status']) {
			case 'ok':
				$status_cell = (new CSpan(_('Ok')))->addClass(ZBX_STYLE_GREEN);
				break;

			case 'failed':
				$failed_step = ($test['lastfailedstep'] > 0) ? ' ' . _s('(step %1$s)', $test['lastfailedstep']) : '';
				$status_text = _('Failed') . $failed_step;
				$status_cell = (new CSpan($status_text))->addClass(ZBX_STYLE_RED);
				break;

			default:
				$status_cell = (new CSpan(_('Unknown')))->addClass(ZBX_STYLE_GREY);
		}

		// Response time cell.
		if ($test['response_time'] !== null) {
			$ms = round($test['response_time'] * 1000);
			$response_cell = $ms . ' ms';
		}
		else {
			$response_cell = (new CSpan('—'))->addClass(ZBX_STYLE_GREY);
		}

		// Last check cell.
		if ($test['lastcheck'] !== null && $test['lastcheck'] > 0) {
			$lastcheck_cell = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $test['lastcheck']);
		}
		else {
			$lastcheck_cell = (new CSpan('—'))->addClass(ZBX_STYLE_GREY);
		}

		// HTTP code cell — red for non-2xx codes.
		if ($test['http_code'] !== null && $test['http_code'] > 0) {
			$code_span = new CSpan($test['http_code']);
			if ($test['http_code'] < 200 || $test['http_code'] >= 300) {
				$code_span->addClass(ZBX_STYLE_RED);
			}
			$http_code_cell = $code_span;
		}
		else {
			$http_code_cell = (new CSpan('—'))->addClass(ZBX_STYLE_GREY);
		}

		$row_cells = [
			new CCol($name_cell),
			new CCol($test['host_name']),
			new CCol($status_cell),
			new CCol($response_cell),
			new CCol($lastcheck_cell),
			new CCol($http_code_cell)
		];

		if ($test['status'] === 'failed') {
			foreach ($row_cells as $ccol) {
				$ccol->addClass(ZBX_STYLE_AVERAGE_BG);
			}
		}

		$table->addRow(
			(new CRow($row_cells))
				->setAttribute('data-hostgroupid', $test['groupid'])
				->setAttribute('data-httptestid', (string) $test['httptestid'])
				->setAttribute('data-webmon-url', $webmon_url_attr)
				->setAttribute('data-site-url', $site_url_attr)
		);
	}
}

(new CWidgetView($data))
	->addItem($table)
	->show();
