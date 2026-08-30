export function HomePage() {
    return (
        <main className="min-h-screen bg-slate-950 text-slate-100">
            <div className="mx-auto flex min-h-screen max-w-6xl items-center px-6 py-16">
                <section className="w-full">
                    <p className="mb-3 text-sm font-medium uppercase tracking-[0.25em] text-teal-400">
                        Wallet App
                    </p>

                    <h1 className="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">
                        Frontend web funcionando con React, TypeScript y
                        Laravel Vite
                    </h1>

                    <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-400">
                        La aplicación web ya está montada dentro de Laravel.
                        El siguiente paso será conectar autenticación mediante
                        Sanctum SPA y comenzar el dashboard financiero.
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
                                Build
                            </p>

                            <p className="mt-2 font-medium">
                                Vite
                            </p>
                        </article>

                        <article className="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                            <p className="text-sm text-slate-400">
                                Runtime
                            </p>

                            <p className="mt-2 font-medium">
                                Docker
                            </p>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    );
}