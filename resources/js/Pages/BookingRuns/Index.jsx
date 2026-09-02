import DataTable from '@/Components/DataTable';
import FlashMessage from '@/Components/FlashMessage';
import Pagination from '@/Components/Pagination';
import RunBookingButton from '@/Components/RunBookingButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { runStatusLabels, triggerLabels } from '@/lib/labels';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', className: 'w-16 text-gray-500' },
    {
        key: 'trigger',
        label: 'Indítás',
        render: (row) => triggerLabels[row.trigger] ?? row.trigger,
    },
    {
        key: 'status',
        label: 'Státusz',
        render: (row) => runStatusLabels[row.status] ?? row.status,
    },
    { key: 'started_at', label: 'Kezdés', className: 'tabular-nums whitespace-nowrap' },
    { key: 'finished_at', label: 'Befejezés', className: 'tabular-nums whitespace-nowrap' },
    { key: 'summary', label: 'Összegzés', className: 'text-gray-600' },
];

export default function Index({ bookingRuns, booking }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);

    const destroy = (id) => {
        if (confirm('Biztosan törli a futást és a kapcsolódó rekordokat?')) {
            setDeletingId(id);
            router.delete(route('booking-runs.destroy', id), {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const actions = (row) => (
        <>
            <Link
                href={route('booking-runs.show', row.id)}
                className="inline-flex items-center justify-center w-8 h-8 rounded bg-brand-600 text-white hover:bg-brand-700"
                title="Részletek"
            >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </Link>
            <button
                onClick={() => destroy(row.id)}
                disabled={deletingId === row.id}
                className="inline-flex items-center justify-center w-8 h-8 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-40"
                title="Törlés"
            >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </>
    );

    const rows = bookingRuns.data.map((row) => ({
        ...row,
        summary: row.summary ?? '—',
        started_at: row.started_at ?? '—',
        finished_at: row.finished_at ?? '—',
    }));

    return (
        <AuthenticatedLayout header="Futások">
            <Head title="Futások" />

            <div className="bg-white rounded-xl shadow-card">
                <div className="flex items-center justify-between px-6 py-4 border-b gap-4">
                    <h1 className="text-lg font-semibold text-gray-800">Foglalási futások</h1>
                    <RunBookingButton
                        canStart={booking?.can_start}
                        isRunning={booking?.is_running}
                    />
                </div>

                <FlashMessage flash={flash} />

                <DataTable columns={columns} rows={rows} actions={actions} emptyText="Még nem volt foglalási futás." />

                <Pagination links={bookingRuns.links} />
            </div>
        </AuthenticatedLayout>
    );
}
