interface SkeletonProps {
    className?: string;
    dark?: boolean;
}

/** Superficie visual de carga, excluida del árbol de accesibilidad. */
export function Skeleton({
    className = '',
    dark = false,
}: SkeletonProps) {
    return (
        <span
            aria-hidden="true"
            className={`skeleton ${dark ? 'skeleton-dark' : ''} ${className}`}
        />
    );
}
