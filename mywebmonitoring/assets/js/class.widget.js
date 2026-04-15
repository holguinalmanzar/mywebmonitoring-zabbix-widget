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


class CWidgetMyWebMonitoring extends CWidget {

	/**
	 * Table body of web monitoring.
	 *
	 * @type {HTMLElement|null}
	 */
	#table_body = null;

	/**
	 * Selected web scenario id (unique per row). Used only for row highlight — many rows can share the same host group.
	 *
	 * @type {string|null}
	 */
	#selected_httptestid = null;

	/**
	 * Selected host group id for dashboard broadcast (can match multiple rows).
	 *
	 * @type {string|null}
	 */
	#selected_hostgroupid = null;

	setContents(response) {
		super.setContents(response);

		this.#table_body = this._contents.querySelector(`.${ZBX_STYLE_LIST_TABLE} tbody`);

		if (this.#table_body == null) {
			return;
		}

		// Bind once per tbody DOM node (tbody is replaced on each refresh; class-level flags would skip the new node).
		if (this.#table_body.dataset.mywebmonitoringClickBound !== '1') {
			this.#table_body.dataset.mywebmonitoringClickBound = '1';
			this.#table_body.addEventListener('click', e => this.#onTableBodyClick(e));
		}

		if (!this.hasEverUpdated() && this.isReferred()) {
			const sel = this.#getDefaultSelectable();

			if (sel !== null) {
				this.#selected_httptestid = String(sel.httptestid);
				this.#selected_hostgroupid = sel.hostgroupid;
				this.#selectRow();
				this.#broadcast();
			}
		}
		else if (this.#selected_httptestid !== null) {
			this.#selectRow();
		}
	}

	onReferredUpdate() {
		if (this.#table_body === null || this.#selected_httptestid !== null) {
			return;
		}

		const sel = this.#getDefaultSelectable();

		if (sel !== null) {
			this.#selected_httptestid = String(sel.httptestid);
			this.#selected_hostgroupid = sel.hostgroupid;
			this.#selectRow();
			this.#broadcast();
		}
	}

	#getDefaultSelectable() {
		const row = this.#table_body.querySelector('[data-httptestid]');

		if (row === null) {
			return null;
		}

		return {
			httptestid: String(row.dataset.httptestid),
			hostgroupid: row.dataset.hostgroupid
		};
	}

	#selectRow() {
		const selected = this.#selected_httptestid !== null ? String(this.#selected_httptestid) : null;
		const rows = this.#table_body.querySelectorAll('[data-httptestid]');

		for (const row of rows) {
			row.classList.toggle(ZBX_STYLE_ROW_SELECTED, String(row.dataset.httptestid) === selected);
		}
	}

	#broadcast() {
		this.broadcast({
			[CWidgetsData.DATA_TYPE_HOST_GROUP_ID]: [this.#selected_hostgroupid],
			[CWidgetsData.DATA_TYPE_HOST_GROUP_IDS]: [this.#selected_hostgroupid]
		});
	}

	#onTableBodyClick(e) {
		if (e.target.closest('a') !== null || e.target.closest('[data-hintbox="1"]') !== null) {
			return;
		}

		const row = e.target.closest('[data-httptestid]');

		if (row !== null) {
			this.#selected_httptestid = String(row.dataset.httptestid);
			this.#selected_hostgroupid = row.dataset.hostgroupid;

			this.#selectRow();
			this.#broadcast();
		}
	}
}
