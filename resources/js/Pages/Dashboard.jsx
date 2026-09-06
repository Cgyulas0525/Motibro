import DataTable from '@/Components/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import RunBookingButton from '@/Components/RunBookingButton';
import StatusBadge from '@/Components/StatusBadge';
import { runStatusLabels, triggerLabels } from '@/lib/labels';
import { Head, Link, usePage } from '@inertiajs/react';

function StatCard({ label, value, sub, bgClass }) {
    return (
        <div className={`${bgClass} rounded-xl px-6 py-5 flex flex-col justify-between min-h-[120px]`}>
            <div className="text-xs font-medium text-gray-300 uppercase tracking-wide mb-3">{label}</div>
            <div className="text-3xl font-bold tabular-nums text-white text-right">{value}</div>
            {sub && <div className="text-xs text-gray-400 mt-1 text-right">{sub}</div>}
        </div>
    );
}

function Panel({ title, href, children }) {
    return (
        <div className="bg-gray-800 rounded-xl overflow-hidden">
            <div className="bg-brand-700 px-5 py-4 flex items-center justify-between gap-3">
                <h2 className="text-sm font-semibold text-white">{title}</h2>
                {href && (
                    <Link href={href} className="text-xs text-brand-100 hover:text-white underline">
                        Kezelés →
                    </Link>
                )}
            </div>
            <div className="bg-white">{children}</div>
        </div>
    );
}

function SchedulerPanel({ stats }) {
    const rows = [
        { label: 'Automatikus futás', value: stats.scheduler_enabled ? 'Bekapcsolva' : 'Kikapcsolva' },
        { label: 'Ablak', value: `${stats.window_start} – ${stats.window_end}` },
        { label: 'Intervallum', value: `${stats.interval_minutes} perc` },
        { label: 'Időzóna', value: stats.timezone },
        { label: 'Utolsó automatikus futás', value: stats.last_scheduled_run_at ?? 'Még nem futott' },
        { label: 'Következő automatikus futás', value: stats.next_scheduled_run_at ?? '—' },
    ];

    return (
        <div className="divide-y divide-gray-700">
            {rows.map(({ label, value }) => (
                <div key={label} className="flex items-center justify-between px-5 py-3 gap-4">
                    <span className="text-sm text-gray-400 shrink-0">{label}</span>
                    <div className="flex-1 bg-gray-700 rounded px-3 py-1.5 text-sm text-right text-gray-100">
                        {value}
                    </div>
                </div>
            ))}
        </div>
    );
}

const ruleColumns = [
    { key: 'sort_order', label: '#', className: 'w-12 text-gray-500' },
    { key: 'label', label: 'Megnevezés' },
    { key: 'weekday_label', label: 'Nap' },
    { key: 'time', label: 'Idő', className: 'tabular-nums' },
    {
        key: 'enabled',
        label: 'Állapot',
        render: (row) => <StatusBadge active={row.enabled} />,
    },
    {
        key: 'waitlist_ok',
        label: 'Várólista',
        render: (row) => (
            <StatusBadge
                active={row.waitlist_ok}
                activeLabel="Igen"
                inactiveLabel="Nem"
            />
        ),
    },
];

const runColumns = [
    { key: 'id', label: 'ID', className: 'w-16 text-gray-500' },
    { key: 'trigger', label: 'Indítás' },
    { key: 'status', label: 'Státusz' },
    { key: 'started_at', label: 'Kezdés' },
    { key: 'finished_at', label: 'Befejezés' },
    { key: 'summary', label: 'Összegzés' },
];

export default function Dashboard({ stats, bookingRules, recentRuns, booking }) {
    const { flash } = usePage().props;
    const runs = recentRuns.map((run) => ({
        ...run,
        trigger: triggerLabels[run.trigger] ?? run.trigger,
        status: runStatusLabels[run.status] ?? run.status,
        summary: run.summary ?? '—',
        started_at: run.started_at ?? '—',
        finished_at: run.finished_at ?? '—',
    }));

    return (
        <AuthenticatedLayout header="Indító pult" mainClassName="bg-gray-900">
            <Head title="Indító pult" />

            <div className="flex flex-col gap-5">
                <FlashMessage flash={flash} />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-gray-400">
                        Kézi foglalás — az aktív időpont-szabályok alapján fut le.
                    </p>
                    <RunBookingButton
                        canStart={booking?.can_start}
                        isRunning={booking?.is_running}
                    />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <StatCard
                        label="Aktív időpontok"
                        value={stats.active_rules}
                        sub={`Összesen ${stats.total_rules} szabály`}
                        bgClass="bg-brand-800"
                    />
                    <StatCard
                        label="Ütemező"
                        value={stats.scheduler_enabled ? 'Bekapcsolva' : 'Kikapcsolva'}
                        bgClass={stats.scheduler_enabled ? 'bg-sky-900' : 'bg-gray-700'}
                    />
                    <StatCard
                        label="Futási ablak"
                        value={`${stats.window_start} – ${stats.window_end}`}
                        sub={stats.timezone}
                        bgClass="bg-violet-900"
                    />
                    <StatCard
                        label="Intervallum"
                        value={`${stats.interval_minutes} perc`}
                        sub={stats.last_scheduled_run_at ? `Utolsó: ${stats.last_scheduled_run_at}` : 'Még nem futott automatikusan'}
                        bgClass="bg-amber-900"
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                    <div className="lg:col-span-2">
                        <Panel title="Foglalási időpontok" href={route('booking-rules.index')}>
                            <DataTable
                                columns={ruleColumns}
                                rows={bookingRules}
                                emptyText="Nincs időpont-szabály. Futtasd a seedert."
                            />
                        </Panel>
                    </div>

                    <div className="bg-gray-800 rounded-xl overflow-hidden">
                        <div className="bg-brand-700 px-5 py-4 flex items-center justify-between gap-3">
                            <h2 className="text-sm font-semibold text-white">Ütemezés beállítások</h2>
                            <Link href={route('settings.scheduler.edit')} className="text-xs text-brand-100 hover:text-white underline">
                                Kezelés →
                            </Link>
                        </div>
                        <SchedulerPanel stats={stats} />
                    </div>
                </div>

                <Panel title="Utolsó futások" href={route('booking-runs.index')}>
                    <DataTable
                        columns={runColumns}
                        rows={runs}
                        emptyText="Még nem volt foglalási futás."
                    />
                </Panel>
            </div>
        </AuthenticatedLayout>
    );
}
