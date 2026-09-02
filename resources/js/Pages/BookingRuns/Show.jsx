import DataTable from '@/Components/DataTable';
import FlashMessage from '@/Components/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { actionLabels, runStatusLabels, triggerLabels } from '@/lib/labels';
import { Head, Link, usePage } from '@inertiajs/react';

const attemptColumns = [
    { key: 'slot_starts_at', label: 'Slot', className: 'tabular-nums whitespace-nowrap' },
    { key: 'rule_label', label: 'Szabály' },
    {
        key: 'action',
        label: 'Eredmény',
        render: (row) => actionLabels[row.action] ?? row.action,
    },
    { key: 'message', label: 'Üzenet', className: 'text-gray-600' },
];

export default function Show({ bookingRun, attempts }) {
    const { flash } = usePage().props;
    const rows = attempts.map((row) => ({
        ...row,
        rule_label: row.rule_label ?? '—',
        message: row.message ?? '—',
    }));

    return (
        <AuthenticatedLayout header={`Futás #${bookingRun.id}`}>
            <Head title={`Futás #${bookingRun.id}`} />

            <div className="space-y-5">
                <FlashMessage flash={flash} />

                <div className="bg-white rounded-xl shadow-card px-6 py-4">
                    <div className="flex items-center justify-between gap-4 mb-4">
                        <h1 className="text-lg font-semibold text-gray-800">Futás #{bookingRun.id}</h1>
                        <Link href={route('booking-runs.index')} className="text-sm text-brand-600 hover:underline">
                            ← Vissza a listához
                        </Link>
                    </div>
                    <dl className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div><dt className="text-gray-500">Indítás</dt><dd>{triggerLabels[bookingRun.trigger] ?? bookingRun.trigger}</dd></div>
                        <div><dt className="text-gray-500">Státusz</dt><dd>{runStatusLabels[bookingRun.status] ?? bookingRun.status}</dd></div>
                        <div><dt className="text-gray-500">Kezdés</dt><dd>{bookingRun.started_at ?? '—'}</dd></div>
                        <div><dt className="text-gray-500">Befejezés</dt><dd>{bookingRun.finished_at ?? '—'}</dd></div>
                        <div className="sm:col-span-2"><dt className="text-gray-500">Összegzés</dt><dd>{bookingRun.summary ?? '—'}</dd></div>
                        {bookingRun.exit_code !== null && (
                            <div><dt className="text-gray-500">Exit kód</dt><dd>{bookingRun.exit_code}</dd></div>
                        )}
                    </dl>
                </div>

                <div className="bg-white rounded-xl shadow-card">
                    <div className="px-6 py-4 border-b">
                        <h2 className="text-sm font-semibold text-gray-800">Kísérletek</h2>
                    </div>
                    <DataTable columns={attemptColumns} rows={rows} emptyText="Nincs kísérlet ehhez a futáshoz." />
                </div>

                {bookingRun.raw_output && (
                    <div className="bg-white rounded-xl shadow-card px-6 py-4">
                        <h2 className="text-sm font-semibold text-gray-800 mb-2">Nyers kimenet</h2>
                        <pre className="text-xs bg-gray-50 rounded p-3 overflow-x-auto text-gray-700 whitespace-pre-wrap">
                            {bookingRun.raw_output}
                        </pre>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
