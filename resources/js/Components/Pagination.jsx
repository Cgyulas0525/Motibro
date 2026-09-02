import { Link } from '@inertiajs/react';

const decodeLabel = (html) => {
    const entities = { '&laquo;': '«', '&raquo;': '»', '&hellip;': '…', '&amp;': '&' };
    return html.replace(/&[a-z]+;/gi, (match) => entities[match] ?? match);
};

export default function Pagination({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className="flex justify-center gap-1 px-6 py-4 border-t">
            {links.map((link, i) => (
                <Link
                    key={i}
                    href={link.url ?? '#'}
                    className={`px-3 py-1 rounded text-sm border ${
                        link.active ? 'bg-brand-600 text-white border-brand-600' : 'text-gray-600 border-gray-300 hover:bg-gray-50'
                    } ${!link.url ? 'opacity-40 pointer-events-none' : ''}`}
                    tabIndex={!link.url ? -1 : undefined}
                    aria-disabled={!link.url}
                >
                    {decodeLabel(link.label)}
                </Link>
            ))}
        </div>
    );
}
