import { useRef } from 'react';

export default function DataTable({ columns, rows, actions, emptyText = 'Nincs adat.', rowClassName, sortKey, sortDir, onSort }) {
    const tbodyRef = useRef(null);

    const renderHeader = (col) => {
        if (!col.sortable || !onSort) {
            return col.label;
        }
        const key = col.sortKey ?? col.key;
        const active = sortKey === key;
        return (
            <button
                type="button"
                onClick={() => onSort(key)}
                className="inline-flex items-center gap-1 uppercase hover:text-white/80 focus:outline-none"
                title="Rendezés"
            >
                <span>{col.label}</span>
                <span className="text-[10px] leading-none">
                    {active ? (sortDir === 'asc' ? '▲' : '▼') : '↕'}
                </span>
            </button>
        );
    };

    const handleKeyDown = (e, index) => {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        e.preventDefault();

        const rowEls = tbodyRef.current?.querySelectorAll('tr[tabindex]');
        if (!rowEls) return;

        const next = e.key === 'ArrowDown' ? index + 1 : index - 1;
        if (next >= 0 && next < rowEls.length) {
            rowEls[next].focus();
        }
    };

    return (
        <table className="w-full text-sm">
            <thead className="bg-brand-800 text-white uppercase text-xs">
                <tr>
                    {columns.map((col) => (
                        <th
                            key={col.key}
                            className={`px-6 py-3 ${col.align === 'right' ? 'text-right' : 'text-left'}`}
                        >
                            {renderHeader(col)}
                        </th>
                    ))}
                    {actions && <th className="px-6 py-3" />}
                </tr>
            </thead>
            <tbody ref={tbodyRef} className="divide-y divide-gray-100">
                {rows.length === 0 ? (
                    <tr>
                        <td
                            colSpan={columns.length + (actions ? 1 : 0)}
                            className="px-6 py-8 text-center text-gray-400"
                        >
                            {emptyText}
                        </td>
                    </tr>
                ) : (
                    rows.map((row, index) => (
                        <tr
                            key={row.id}
                            tabIndex={0}
                            onKeyDown={(e) => handleKeyDown(e, index)}
                            className={`hover:bg-gray-50 focus:bg-brand-400/30 focus:outline-none ${rowClassName ? rowClassName(row) : ''}`}
                        >
                            {columns.map((col) => (
                                <td
                                    key={col.key}
                                    className={`px-6 py-3 ${col.className ?? ''} ${col.align === 'right' ? 'text-right tabular-nums' : ''}`}
                                >
                                    {col.render ? col.render(row) : row[col.key]}
                                </td>
                            ))}
                            {actions && (
                                <td className="px-6 py-3 text-right space-x-1">
                                    {actions(row)}
                                </td>
                            )}
                        </tr>
                    ))
                )}
            </tbody>
        </table>
    );
}
