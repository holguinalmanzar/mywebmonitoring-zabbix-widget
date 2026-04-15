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


namespace Modules\Mywebmonitoring;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getDefaultName(): string {
		return _('My Web Monitoring');
	}

	public function getDefaults(): array {
		$defaults = parent::getDefaults();

		// Belt-and-braces: dashboard JS requires defaults[type].size to exist.
		if (!array_key_exists('size', $defaults) || !is_array($defaults['size'])
				|| !array_key_exists('width', $defaults['size'])
				|| !array_key_exists('height', $defaults['size'])) {
			$defaults['size'] = [
				'width'  => 18,
				'height' => 5
			];
		}

		return $defaults;
	}
}
