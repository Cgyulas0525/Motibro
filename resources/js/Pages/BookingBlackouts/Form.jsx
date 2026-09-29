import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function BlackoutForm({ title, header, action, method = 'post', defaults }) {
    const { data, setData, post, patch, processing, errors } = useForm({
        starts_on: defaults?.starts_on ?? '',
        ends_on: defaults?.ends_on ?? '',
        note: defaults?.note ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (method === 'patch') {
            patch(action);
        } else {
            post(action);
        }
    };

    return (
        <AuthenticatedLayout header={header}>
            <Head title={title} />

            <div className="max-w-lg bg-white rounded-xl shadow-card">
                <div className="px-6 py-4 border-b">
                    <h1 className="text-lg font-semibold text-gray-800">{title}</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Egy nap: csak a kezdetet töltsd ki. Több nap: tól–ig.
                    </p>
                </div>

                <form onSubmit={submit} className="px-6 py-4 space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="starts_on" className="block text-sm font-medium text-gray-700 mb-1">
                                Kezdete *
                            </label>
                            <input
                                id="starts_on"
                                type="date"
                                value={data.starts_on}
                                onChange={(e) => setData('starts_on', e.target.value)}
                                className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            {errors.starts_on && <p className="text-red-600 text-xs mt-1">{errors.starts_on}</p>}
                        </div>
                        <div>
                            <label htmlFor="ends_on" className="block text-sm font-medium text-gray-700 mb-1">
                                Vége
                            </label>
                            <input
                                id="ends_on"
                                type="date"
                                value={data.ends_on}
                                onChange={(e) => setData('ends_on', e.target.value)}
                                className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            {errors.ends_on && <p className="text-red-600 text-xs mt-1">{errors.ends_on}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="note" className="block text-sm font-medium text-gray-700 mb-1">
                            Megjegyzés
                        </label>
                        <input
                            id="note"
                            type="text"
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            placeholder="pl. nyári szabadság"
                            className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        />
                        {errors.note && <p className="text-red-600 text-xs mt-1">{errors.note}</p>}
                    </div>

                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={route('booking-blackouts.index')} className="text-sm text-gray-600 hover:underline">
                            Mégse
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-brand-600 text-white text-sm rounded hover:bg-brand-700 disabled:opacity-50"
                        >
                            {processing ? 'Mentés...' : 'Mentés'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
