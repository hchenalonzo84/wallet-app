import {
    useState,
} from 'react';

import { useAuth } from '../hooks/useAuth';

/**
 * Página inicial mostrada únicamente a usuarios autenticados.
 */
export function HomePage() {
    const {
        user,
        logout,
    } = useAuth();

    const [isLoggingOut, setIsLoggingOut] = useState<boolean>(false);

    // Cierra la sesión y permite que ProtectedRoute redirija al login.
    const handleLogout = async (): Promise<void> => {
        setIsLoggingOut(true);

        try {
            await logout();
        } finally {
            setIsLoggingOut(false);
        }
    };

    return (
        <main className="min-h-screen bg-slate-950 text-slate-100">
            <div className="mx-auto min-h-screen max-w-6xl px-6 py-12">
                <header className="flex flex-col justify-between gap-6 border-b border-slate-800 pb-8 sm:flex-row sm:items-center">
                    <div>
                        <p className="text-sm font-medium uppercase tracking-[0.25em] text-teal-400">
                            Wallet App
                        </p>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                            Panel financiero
                        </h1>
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
                </header>

                <section className="py-12">
                    <p className="max-w-2xl text-lg leading-8 text-slate-400">
                        La autenticación web mediante Laravel Sanctum ya está
                        integrada. Desde aquí construiremos el dashboard y los
                        módulos financieros.
                    </p>

                    <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article className="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                            <p className="text-sm text-slate-400">
                                Backend
                            </p>

                            <p className="mt-2 font-medium">
                                Laravel 13
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                            <p className="text-sm text-slate-400">
                                Web
                            </p>

                            <p className="mt-2 font-medium">
                                React + TypeScript
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                            <p className="text-sm text-slate-400">
                                Autenticación
                            </p>

                            <p className="mt-2 font-medium">
                                Sanctum SPA
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                            <p className="text-sm text-slate-400">
                                Sesión
                            </p>

                            <p className="mt-2 font-medium">
                                Cookie + CSRF
                            </p>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    );
}