import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function RunBookingButton({ canStart, isRunning, className = '' }) {
    const [processing, setProcessing] = useState(false);

    const start = () => {
        if (!canStart || isRunning || processing) return;

        if (!confirm('Elindítod a foglalást most? (Kézi futás — az ütemezési ablak figyelmen kívül hagyva.)')) {
            return;
        }

        setProcessing(true);
        router.post(route('booking-runs.trigger'), {}, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    const disabled = !canStart || isRunning || processing;
    const label = processing
        ? 'Indítás...'
        : isRunning
            ? 'Futás folyamatban...'
            : 'Foglalás indítása';

    return (
        <button
            type="button"
            onClick={start}
            disabled={disabled}
            className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded transition-colors ${
                disabled
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    : 'bg-brand-600 text-white hover:bg-brand-700'
            } ${className}`}
        >
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            {label}
        </button>
    );
}
