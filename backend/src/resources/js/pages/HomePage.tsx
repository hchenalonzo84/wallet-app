import { Link } from 'react-router-dom';

import { AppHeader } from '../components/AppHeader';

/**
 * Dashboard principal de Wallet App.
 */
export function HomePage() {
    return (
        <main className="min-h-screen bg-slate-950 text-slate-100">
            <div className="mx-auto min-h-screen max-w-6xl px-6 py-12">
                <AppHeader />

                <section className="py-12">
                    <p className="text-sm font-medium uppercase tracking-[0.2em] text-teal-400">
                        Panel financiero
                    </p>

                    <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                        Administra tus finanzas
                    </h1>

                    <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-400">
                        Organiza tu dinero en bolsillos y registra desde aquí
                        las operaciones de Wallet App.
                    </p>

                    <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            to="/pockets"
                            className="group rounded-2xl border border-slate-800 bg-slate-900 p-6 transition hover:border-teal-800 hover:bg-slate-900/80"
                        >
                            <p className="text-sm text-teal-400">
                                Disponible
                            </p>

                            <h2 className="mt-2 text-xl font-semibold">
                                Bolsillos
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-slate-400">
                                Crea y administra los bolsillos virtuales
                                utilizados para organizar tu dinero.
                            </p>

                            <p className="mt-6 text-sm font-medium text-teal-300">
                                Administrar →
                            </p>
                        </Link>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
                            <p className="text-sm text-slate-500">
                                Próximamente
                            </p>

                            <h2 className="mt-2 text-xl font-semibold text-slate-300">
                                Movimientos
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-slate-500">
                                Depósitos, retiros, ajustes y movimientos
                                financieros.
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
                            <p className="text-sm text-slate-500">
                                Próximamente
                            </p>

                            <h2 className="mt-2 text-xl font-semibold text-slate-300">
                                Transferencias
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-slate-500">
                                Transferencias internas entre tus bolsillos.
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
                            <p className="text-sm text-slate-500">
                                Próximamente
                            </p>

                            <h2 className="mt-2 text-xl font-semibold text-slate-300">
                                Reportes
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-slate-500">
                                Consulta el comportamiento de tus finanzas
                                por períodos.
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
                            <p className="text-sm text-slate-500">
                                Próximamente
                            </p>

                            <h2 className="mt-2 text-xl font-semibold text-slate-300">
                                Pagos recurrentes
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-slate-500">
                                Administra tus pagos automáticos y sus próximas
                                ejecuciones.
                            </p>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    );
}