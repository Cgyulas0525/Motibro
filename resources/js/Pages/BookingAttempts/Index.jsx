import DataTable from '@/Components/DataTable';
import FlashMessage from '@/Components/FlashMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { actionLabels } from '@/lib/labels';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', className: 'w-16 text-gray-500' },
    {
        key: 'booking_run_id',
        label: 'Futás',
        render: (row) => (
            <Link href={route('booking-runs.show', row.booking_run_id)} className="text-brand-600 hover:underline">
                #{row.booking_run_id}
            </Link>
        ),
    },
    { key: 'slot_starts_at', label: 'Slot', className: 'tabular-nums whitespace-nowrap' },
    { key: 'rule_label', label: 'Szabály' },
    {
        key: 'action',
        label: 'Eredmény',
        render: (row) => actionLabels[row.action] ?? row.action,
    },
    { key: 'message', label: 'Üzenet', className: 'text-gray-600' },
];

export default function Index({ bookingAttempts }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);

    const destroy = (id) => {
        if (confirm('Biztosan törli a kísérletet?')) {
            setDeletingId(id);
            router.delete(route('booking-attempts.destroy', id), {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const actions = (row) => (
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
    );

    const rows = bookingAttempts.data.map((row) => ({
        ...row,
        rule_label: row.rule_label ?? '—',
        message: row.message ?? '—',
    }));

    return (
        <AuthenticatedLayout header="Kísérletek">
            <Head title="Kísérletek" />

            <div className="bg-white rounded-xl shadow-card">
                <div className="px-6 py-4 border-b">
                    <h1 className="text-lg font-semibold text-gray-800">Foglalási kísérletek</h1>
                </div>

                <FlashMessage flash={flash} />

                <DataTable columns={columns} rows={rows} actions={actions} emptyText="Még nincs kísérlet." />

                <Pagination links={bookingAttempts.links} />
            </div>
        </AuthenticatedLayout>
    );
}
