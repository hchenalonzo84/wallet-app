import { useState } from 'react';

import {
    Link,
    NavLink,
} from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';

/**
 * Encabezado común para las páginas privadas de Wallet App.
 */
export function AppHeader() {
    const {
        user,
        logout,
    } = useAuth();

    const [isLoggingOut, setIsLoggingOut] = useState<boolean>(false);

    const handleLogout = async (): Promise<void> => {
        setIsLoggingOut(true);

        try {
            await logout();
        } finally {
            setIsLoggingOut(false);
        }
    };

    const navLinkClass = ({
        isActive,
    }: {
        isActive: boolean;
    }): string => {
        const shared = 'inline-flex min-h-11 items-center border-b-2 px-1 text-sm font-semibold transition-colors duration-150';

        return `${shared} ${isActive
            ? 'border-[var(--primary)] text-[var(--text-primary)]'
            : 'border-transparent text-[var(--text-secondary)] hover:border-[var(--border-strong)] hover:text-[var(--text-primary)]'}`;
    };

    return (
        <header className="relative border-b border-[var(--border-strong)] pb-4">
            <a
                href="#main-content"
                className="absolute left-0 top-0 z-20 -translate-y-[160%] bg-[var(--charcoal)] px-4 py-3 text-sm font-semibold text-[var(--on-charcoal)] transition-transform focus:translate-y-0"
            >
                Ir al contenido
            </a>

            <div className="grid grid-cols-[1fr_auto] items-start gap-x-4 gap-y-4 sm:items-center lg:grid-cols-[auto_1fr_auto] lg:gap-x-10">
                <Link
                    to="/"
                    className="inline-flex min-h-11 w-fit items-center gap-3 text-sm font-bold uppercase tracking-[0.18em] text-[var(--text-primary)]"
                >
                    <span
                        aria-hidden="true"
                        className="grid h-8 w-8 place-items-center bg-[var(--primary)] text-xs font-bold text-white shadow-[2px_2px_0_var(--charcoal)]"
                    >
                        W
                    </span>
                    <span className="hidden min-[360px]:inline">
                        Wallet App
                    </span>
                </Link>

                <nav
                    aria-label="Navegación principal"
                    className="order-3 col-span-2 flex gap-6 lg:order-none lg:col-span-1"
                >
                    <NavLink
                        to="/"
                        end
                        className={navLinkClass}
                    >
                        Resumen
                    </NavLink>

                    <NavLink
                        to="/pockets"
                        className={navLinkClass}
                    >
                        Bolsillos
                    </NavLink>
                </nav>

                <div className="flex min-w-0 items-center justify-end gap-4">
                    <div className="hidden min-w-0 items-center gap-3 sm:flex">
                        <span
                            aria-hidden="true"
                            className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--secondary-soft)] text-xs font-bold uppercase text-[var(--secondary)]"
                        >
                            {user?.name?.charAt(0) ?? 'U'}
                        </span>

                        <div className="min-w-0 text-right">
                            <p className="truncate text-sm font-semibold text-[var(--text-primary)]">
                                {user?.name}
                            </p>

                            <p className="max-w-48 truncate text-xs text-[var(--text-secondary)]">
                                {user?.email}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        disabled={isLoggingOut}
                        onClick={() => void handleLogout()}
                        className="button button-secondary shrink-0 px-3 sm:px-4"
                    >
                        {isLoggingOut
                            ? 'Cerrando…'
                            : 'Salir'}
                    </button>
                </div>
            </div>
        </header>
    );
}
