import DataTable from '@/Components/DataTable';
import FlashMessage from '@/Components/FlashMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { slotStatusLabels } from '@/lib/labels';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const columns = [
    { key: 'slot_starts_at', label: 'Slot', className: 'tabular-nums whitespace-nowrap' },
    { key: 'rule_label', label: 'Szabály' },
    {
        key: 'status',
        label: 'Státusz',
        render: (row) => slotStatusLabels[row.status] ?? row.status,
    },
    { key: 'booked_at', label: 'Foglalva', className: 'tabular-nums whitespace-nowrap' },
];

export default function Index({ slotBookings }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);

    const destroy = (id) => {
        if (confirm('Biztosan törli? A slot újra foglalható lesz.')) {
            setDeletingId(id);
            router.delete(route('booking-slot-bookings.destroy', id), {
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

    const rows = slotBookings.data.map((row) => ({
        ...row,
        rule_label: row.rule_label ?? '—',
    }));

    return (
        <AuthenticatedLayout header="Foglalt slotok">
            <Head title="Foglalt slotok" />

            <div className="bg-white rounded-xl shadow-card">
                <div className="px-6 py-4 border-b">
                    <h1 className="text-lg font-semibold text-gray-800">Foglalt slotok</h1>
                    <p className="text-sm text-gray-500 mt-1">Slotonként max. egy sikeres foglalás nyilvántartása.</p>
                </div>

                <FlashMessage flash={flash} />

                <DataTable columns={columns} rows={rows} actions={actions} emptyText="Még nincs foglalt slot." />

                <Pagination links={slotBookings.links} />
            </div>
        </AuthenticatedLayout>
    );
}
