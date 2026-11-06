// Ikon fase bulan custom, geometris sederhana, stroke 1.5px (DESIGN_SYSTEM.md §4, §5).

type MoonProps = { size?: number; className?: string; label?: string };

export function MoonFull({ size = 16, className, label }: MoonProps) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 16 16"
            className={className}
            role={label ? 'img' : undefined}
            aria-label={label}
            aria-hidden={label ? undefined : true}
        >
            <circle
                cx="8"
                cy="8"
                r="6.25"
                className="fill-kunyit stroke-kunyit-800"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function MoonNew({ size = 16, className, label }: MoonProps) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 16 16"
            className={className}
            role={label ? 'img' : undefined}
            aria-label={label}
            aria-hidden={label ? undefined : true}
        >
            <circle
                cx="8"
                cy="8"
                r="6.25"
                className="fill-mangsi stroke-mangsi"
                strokeWidth="1.5"
            />
        </svg>
    );
}
