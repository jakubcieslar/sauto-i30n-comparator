import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    sort(event) {
        const th = event.currentTarget;
        const index = parseInt(th.dataset.sortIndex, 10);
        const type = th.dataset.sortType ?? 'string';
        const direction = th.dataset.sortDirection === 'asc' ? 'desc' : 'asc';

        this.element.querySelectorAll('th[data-sort-index]').forEach((h) => {
            if (h !== th) delete h.dataset.sortDirection;
        });
        th.dataset.sortDirection = direction;

        const tbody = this.element.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const sign = direction === 'asc' ? 1 : -1;

        rows.sort((a, b) => {
            const av = this.cellValue(a, index, type);
            const bv = this.cellValue(b, index, type);
            if (av === null && bv === null) return 0;
            if (av === null) return 1;
            if (bv === null) return -1;
            if (type === 'number') return sign * (av - bv);
            return sign * av.localeCompare(bv, 'cs');
        });

        rows.forEach((r) => tbody.appendChild(r));
    }

    cellValue(row, index, type) {
        const cell = row.children[index];
        if (!cell) return null;
        const raw = (cell.dataset.sortValue ?? cell.textContent).trim();
        if (raw === '' || raw === '–') return null;
        if (type === 'number') {
            const n = Number(raw);
            return Number.isFinite(n) ? n : null;
        }
        return raw.toLocaleLowerCase('cs');
    }
}
