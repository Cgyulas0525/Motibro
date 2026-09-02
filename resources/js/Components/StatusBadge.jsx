export default function StatusBadge({ active, activeLabel = 'Aktív', inactiveLabel = 'Inaktív' }) {
    return (
        <span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
            active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'
        }`}>
            {active ? activeLabel : inactiveLabel}
        </span>
    );
}
