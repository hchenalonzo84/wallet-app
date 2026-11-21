import type { ReactNode } from 'react';

interface AuthLayoutProps {
    variant: 'login' | 'register';
    title: string;
    description: string;
    children: ReactNode;
    footer: ReactNode;
    isCheckingSession: boolean;
}

const loginPrinciples = [
    'Saldos derivados de movimientos',
    'Fondos organizados por propósito',
    'Historial financiero preservado',
];

const registerSteps = [
    ['01', 'Crea tu acceso'],
    ['02', 'Define tus bolsillos'],
    ['03', 'Consulta saldos claros'],
];

/** Marco compartido para autenticación con contenido contextual por flujo. */
export function AuthLayout({
    variant,
    title,
    description,
    children,
    footer,
    isCheckingSession,
}: AuthLayoutProps) {
    return (
        <main className="app-shell grid min-h-dvh lg:grid-cols-[minmax(22rem,0.85fr)_minmax(30rem,1.15fr)]">
            <aside className="relative hidden overflow-hidden bg-[var(--charcoal)] p-10 text-[var(--on-charcoal)] lg:flex lg:flex-col lg:justify-between xl:p-14">
                <div
                    aria-hidden="true"
                    className="absolute -right-20 top-24 h-64 w-64 rounded-full border-[4rem] border-[var(--primary)] opacity-20"
                />

                <div className="relative inline-flex items-center gap-3 text-sm font-bold uppercase tracking-[0.18em]">
                    <span className="grid h-9 w-9 place-items-center bg-[var(--primary)] text-xs shadow-[3px_3px_0_var(--secondary)]">
                        W
                    </span>
                    Wallet App
                </div>

                {variant === 'login' ? (
                    <div className="relative max-w-md view-enter">
                        <p className="text-xs font-bold uppercase tracking-[0.18em] text-[var(--primary-light)]">
                            Tu espacio financiero
                        </p>
                        <h2 className="mt-4 text-4xl font-semibold leading-tight tracking-[-0.04em]">
                            Tus fondos, ordenados con intención.
                        </h2>
                        <ul className="mt-10 border-t border-[var(--charcoal-soft)]">
                            {loginPrinciples.map((principle, index) => (
                                <li
                                    key={principle}
                                    className="flex items-center gap-4 border-b border-[var(--charcoal-soft)] py-4 text-sm text-[var(--on-charcoal-muted)]"
                                >
                                    <span className="font-semibold tabular-nums text-[var(--primary-light)]">
                                        0{index + 1}
                                    </span>
                                    {principle}
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <div className="relative max-w-md view-enter">
                        <p className="text-xs font-bold uppercase tracking-[0.18em] text-[var(--secondary-light)]">
                            Una base clara
                        </p>
                        <h2 className="mt-4 text-4xl font-semibold leading-tight tracking-[-0.04em]">
                            Empieza por darle propósito a tu dinero.
                        </h2>
                        <ol className="mt-10 grid gap-3">
                            {registerSteps.map(([number, label], index) => (
                                <li
                                    key={number}
                                    className={`flex items-center gap-4 border p-4 ${index === 0
                                        ? 'border-[var(--secondary-light)] bg-[var(--secondary)]/10'
                                        : 'border-[var(--charcoal-soft)]'}`}
                                >
                                    <span className="text-sm font-bold tabular-nums text-[var(--secondary-light)]">
                                        {number}
                                    </span>
                                    <span className="text-sm text-[var(--on-charcoal-muted)]">
                                        {label}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </div>
                )}

                <p className="relative text-xs leading-5 text-[var(--on-charcoal-muted)]">
                    Wallet App organiza fondos virtuales sin alterar el origen de tus saldos.
                </p>
            </aside>

            <section
                aria-labelledby="auth-title"
                aria-busy={isCheckingSession}
                className="flex min-h-dvh items-center px-4 py-8 sm:px-8 lg:px-12 xl:px-20"
            >
                <div className="view-enter mx-auto w-full max-w-md">
                    <div className="mb-10 flex items-center gap-3 text-sm font-bold uppercase tracking-[0.18em] text-[var(--text-primary)] lg:hidden">
                        <span className="grid h-9 w-9 place-items-center bg-[var(--primary)] text-xs text-white shadow-[3px_3px_0_var(--secondary)]">
                            W
                        </span>
                        Wallet App
                    </div>

                    <p className="eyebrow">
                        {variant === 'login'
                            ? 'Acceso seguro'
                            : 'Nueva cuenta'}
                    </p>

                    <h1
                        id="auth-title"
                        className="mt-3 text-4xl font-semibold tracking-[-0.045em]"
                    >
                        {title}
                    </h1>

                    <p className="mt-3 max-w-sm text-base leading-7 text-[var(--text-secondary)]">
                        {description}
                    </p>

                    {isCheckingSession && (
                        <p
                            role="status"
                            className="mt-5 border-l-2 border-[var(--secondary)] pl-3 text-sm text-[var(--text-secondary)]"
                        >
                            Comprobando sesión…
                        </p>
                    )}

                    <div className="mt-8">
                        {children}
                    </div>

                    <div className="mt-8 border-t border-[var(--border)] pt-6 text-sm text-[var(--text-secondary)]">
                        {footer}
                    </div>
                </div>
            </section>
        </main>
    );
}
