import { useEffect, useState } from 'react';

export default function FlashMessage({ flash }) {
    const [show, setShow] = useState(false);

    useEffect(() => {
        if (!flash?.success && !flash?.error) return;
        setShow(true);
        const t = setTimeout(() => setShow(false), 3000);
        return () => clearTimeout(t);
    }, [flash]);

    if (!show) return null;

    if (flash?.success) {
        return (
            <div className="mx-6 mt-4 px-4 py-2 bg-green-100 text-green-800 rounded text-sm">
                {flash.success}
            </div>
        );
    }

    if (flash?.error) {
        return (
            <div className="mx-6 mt-4 px-4 py-2 bg-red-100 text-red-800 rounded text-sm">
                {flash.error}
            </div>
        );
    }

    return null;
}
