import { useState } from 'react';

import { Link } from 'react-router-dom';

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

    // Finaliza la sesión actual del navegador.
    const handleLogout = async (): Promise<void> => {
        setIsLoggingOut(true);

        try {
            await logout();
        } finally {
            setIsLoggingOut(false);
        }
    };

    return (
        <header className="border-b border-slate-800 pb-8">
            <div className="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                <div>
                    <Link
                        to="/"
                        className="text-sm font-medium uppercase tracking-[0.25em] text-teal-400"
                    >
                        Wallet App
                    </Link>

                    <nav className="mt-4 flex flex-wrap gap-2">
                        <Link
                            to="/"
                            className="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900 hover:text-white"
                        >
                            Panel
                        </Link>

                        <Link
                            to="/pockets"
                            className="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900 hover:text-white"
                        >
                            Bolsillos
                        </Link>
                    </nav>
                </div>

                <div className="flex items-center gap-4">
                    <div className="text-right">
                        <p className="font-medium">
                            {user?.name}
                        </p>

                        <p className="text-sm text-slate-400">
                            {user?.email}
                        </p>
                    </div>

                    <button
                        type="button"
                        disabled={isLoggingOut}
                        onClick={() => void handleLogout()}
                        className="rounded-xl border border-slate-700 px-4 py-2 text-sm font-medium transition hover:border-slate-500 hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isLoggingOut
                            ? 'Cerrando...'
                            : 'Cerrar sesión'}
                    </button>
                </div>
            </div>
        </header>
    );
}