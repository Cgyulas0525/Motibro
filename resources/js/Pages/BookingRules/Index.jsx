import DataTable from '@/Components/DataTable';
import FlashMessage from '@/Components/FlashMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const columns = [
    { key: 'sort_order', label: '#', className: 'w-12 text-gray-500' },
    { key: 'label', label: 'Megjegyzés' },
    { key: 'weekday_label', label: 'Nap' },
    { key: 'time', label: 'Idő', className: 'tabular-nums' },
    {
        key: 'enabled',
        label: 'Aktív',
        render: (row) => <StatusBadge active={row.enabled} />,
    },
    {
        key: 'waitlist_ok',
        label: 'Várólista',
        render: (row) => (
            <StatusBadge active={row.waitlist_ok} activeLabel="Igen" inactiveLabel="Nem" />
        ),
    },
];

export default function Index({ bookingRules }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);

    const destroy = (id) => {
        if (confirm('Biztosan törli az időpontot?')) {
            setDeletingId(id);
            router.delete(route('booking-rules.destroy', id), {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const actions = (row) => (
        <>
            <Link
                href={route('booking-rules.edit', row.id)}
                className="inline-flex items-center justify-center w-8 h-8 rounded bg-green-600 text-white hover:bg-green-700"
                title="Szerkesztés"
            >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
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

    return (
        <AuthenticatedLayout header="Időpontok">
            <Head title="Időpontok" />

            <div className="bg-white rounded-xl shadow-card">
                <div className="flex items-center justify-between px-6 py-4 border-b gap-4">
                    <h1 className="text-lg font-semibold text-gray-800">Foglalási időpontok</h1>
                    <Link
                        href={route('booking-rules.create')}
                        className="px-4 py-2 bg-brand-600 text-white text-sm rounded hover:bg-brand-700 shrink-0"
                    >
                        + Új időpont
                    </Link>
                </div>

                <FlashMessage flash={flash} />

                <DataTable
                    columns={columns}
                    rows={bookingRules.data}
                    actions={actions}
                    emptyText="Nincs időpont-szabály."
                />

                <Pagination links={bookingRules.links} />
            </div>
        </AuthenticatedLayout>
    );
}
