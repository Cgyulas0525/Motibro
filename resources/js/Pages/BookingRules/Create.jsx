import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ weekdays }) {
    const { data, setData, post, processing, errors } = useForm({
        label: '',
        weekday: '1',
        time: '08:00',
        enabled: true,
        waitlist_ok: false,
        sort_order: 0,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('booking-rules.store'));
    };

    return (
        <AuthenticatedLayout header="Időpontok / Új">
            <Head title="Új időpont" />

            <div className="max-w-lg bg-white rounded-xl shadow-card">
                <div className="px-6 py-4 border-b">
                    <h1 className="text-lg font-semibold text-gray-800">Új időpont</h1>
                </div>

                <form onSubmit={submit} className="px-6 py-4 space-y-4">
                    <div>
                        <label htmlFor="weekday" className="block text-sm font-medium text-gray-700 mb-1">Nap *</label>
                        <select
                            id="weekday"
                            value={data.weekday}
                            onChange={(e) => setData('weekday', e.target.value)}
                            className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        >
                            {weekdays.map((day) => (
                                <option key={day.value} value={day.value}>{day.label}</option>
                            ))}
                        </select>
                        {errors.weekday && <p className="text-red-600 text-xs mt-1">{errors.weekday}</p>}
                    </div>

                    <div>
                        <label htmlFor="time" className="block text-sm font-medium text-gray-700 mb-1">Idő *</label>
                        <input
                            id="time"
                            type="time"
                            value={data.time}
                            onChange={(e) => setData('time', e.target.value)}
                            className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        />
                        {errors.time && <p className="text-red-600 text-xs mt-1">{errors.time}</p>}
                    </div>

                    <div>
                        <label htmlFor="label" className="block text-sm font-medium text-gray-700 mb-1">Megjegyzés</label>
                        <input
                            id="label"
                            type="text"
                            value={data.label}
                            onChange={(e) => setData('label', e.target.value)}
                            placeholder="pl. Hétfő reggel"
                            className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        />
                        {errors.label && <p className="text-red-600 text-xs mt-1">{errors.label}</p>}
                    </div>

                    <div>
                        <label htmlFor="sort_order" className="block text-sm font-medium text-gray-700 mb-1">Sorrend</label>
                        <input
                            id="sort_order"
                            type="number"
                            min="0"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', e.target.value)}
                            className="w-32 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        />
                        {errors.sort_order && <p className="text-red-600 text-xs mt-1">{errors.sort_order}</p>}
                    </div>

                    <div className="flex flex-col gap-2">
                        <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                checked={data.enabled}
                                onChange={(e) => setData('enabled', e.target.checked)}
                                className="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            />
                            Aktív
                        </label>
                        <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                checked={data.waitlist_ok}
                                onChange={(e) => setData('waitlist_ok', e.target.checked)}
                                className="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            />
                            Várólistára jelentkezhet
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={route('booking-rules.index')} className="text-sm text-gray-600 hover:underline">
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
