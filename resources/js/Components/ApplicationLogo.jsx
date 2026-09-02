export default function ApplicationLogo({ className = 'h-8 w-8', ...props }) {
    return (
        <svg
            viewBox="0 0 40 40"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            role="img"
            aria-label="Motibro"
            {...props}
        >
            <rect width="40" height="40" rx="9" className="fill-brand-800" />
            <text
                x="20"
                y="21"
                textAnchor="middle"
                dominantBaseline="central"
                fill="white"
                fontFamily="Figtree, ui-sans-serif, system-ui, sans-serif"
                fontWeight="800"
                fontSize="15"
                letterSpacing="-1"
            >
                MB
            </text>
        </svg>
    );
}
