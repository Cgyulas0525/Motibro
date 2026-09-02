import FlashMessage from '@/Components/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Scheduler({ scheduler, timezones }) {
    const { flash } = usePage().props;
    const { data, setData, patch, processing, errors } = useForm({
        enabled: scheduler.enabled,
        window_start: scheduler.window_start,
        window_end: scheduler.window_end,
        interval_minutes: scheduler.interval_minutes,
        timezone: scheduler.timezone,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('settings.scheduler.update'));
    };

    return (
        <AuthenticatedLayout header="Ütemezés">
            <Head title="Ütemezés" />

            <div className="max-w-lg bg-white rounded-xl shadow-card">
                <div className="px-6 py-4 border-b">
                    <h1 className="text-lg font-semibold text-gray-800">Automatikus futás beállításai</h1>
                    {scheduler.last_scheduled_run_at && (
                        <p className="text-sm text-gray-500 mt-1">
                            Utolsó automatikus futás: {scheduler.last_scheduled_run_at}
                        </p>
                    )}
                </div>

                <FlashMessage flash={flash} />

                <form onSubmit={submit} className="px-6 py-4 space-y-4">
                    <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            checked={data.enabled}
                            onChange={(e) => setData('enabled', e.target.checked)}
                            className="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                        />
                        Automatikus futás bekapcsolva
                    </label>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="window_start" className="block text-sm font-medium text-gray-700 mb-1">
                                Ablak kezdete *
                            </label>
                            <input
                                id="window_start"
                                type="time"
                                value={data.window_start}
                                onChange={(e) => setData('window_start', e.target.value)}
                                className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            {errors.window_start && <p className="text-red-600 text-xs mt-1">{errors.window_start}</p>}
                        </div>
                        <div>
                            <label htmlFor="window_end" className="block text-sm font-medium text-gray-700 mb-1">
                                Ablak vége *
                            </label>
                            <input
                                id="window_end"
                                type="time"
                                value={data.window_end}
                                onChange={(e) => setData('window_end', e.target.value)}
                                className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            {errors.window_end && <p className="text-red-600 text-xs mt-1">{errors.window_end}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="interval_minutes" className="block text-sm font-medium text-gray-700 mb-1">
                            Intervallum (perc) *
                        </label>
                        <input
                            id="interval_minutes"
                            type="number"
                            min="1"
                            max="60"
                            value={data.interval_minutes}
                            onChange={(e) => setData('interval_minutes', e.target.value)}
                            className="w-32 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        />
                        {errors.interval_minutes && <p className="text-red-600 text-xs mt-1">{errors.interval_minutes}</p>}
                    </div>

                    <div>
                        <label htmlFor="timezone" className="block text-sm font-medium text-gray-700 mb-1">Időzóna *</label>
                        <select
                            id="timezone"
                            value={data.timezone}
                            onChange={(e) => setData('timezone', e.target.value)}
                            className="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                        >
                            {timezones.map((tz) => (
                                <option key={tz.value} value={tz.value}>{tz.label}</option>
                            ))}
                        </select>
                        {errors.timezone && <p className="text-red-600 text-xs mt-1">{errors.timezone}</p>}
                    </div>

                    <div className="flex justify-end pt-2">
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
