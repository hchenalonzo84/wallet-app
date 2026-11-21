import {
    useCallback,
    useEffect,
    useState,
} from 'react';

import { Link } from 'react-router-dom';

import { AppHeader } from '../components/AppHeader';
import { Skeleton } from '../components/Skeleton';
import { useDocumentTitle } from '../hooks/useDocumentTitle';
import { pocketService } from '../services/pockets';

import type { PocketWithBalance } from '../types/pocket';

// Formatea para presentación los importes calculados por Laravel.
function formatCurrency(value: string): string {
    return new Intl.NumberFormat('es-GT', {
        style: 'currency',
        currency: 'GTQ',
        minimumFractionDigits: 2,
    }).format(Number(value));
}

/**
 * Resumen financiero principal de Wallet App.
 */
export function HomePage() {
    useDocumentTitle('Resumen');

    const [pockets, setPockets] = useState<PocketWithBalance[]>([]);
    const [totalBalance, setTotalBalance] = useState<string>('0.00');
    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [loadError, setLoadError] = useState<string | null>(null);

    const loadSummary = useCallback(async (): Promise<void> => {
        setIsLoading(true);
        setLoadError(null);

        try {
            const response = await pocketService.getBalances();

            setPockets(response.data);
            setTotalBalance(response.meta.total_balance);
        } catch {
            setLoadError(
                'No fue posible cargar tu resumen financiero.',
            );
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        void loadSummary();
    }, [loadSummary]);

    const visiblePockets = pockets.slice(0, 4);

    return (
        <main className="app-shell">
            <div className="mx-auto min-h-screen max-w-7xl px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
                <AppHeader />

                <section
                    id="main-content"
                    tabIndex={-1}
                    aria-labelledby="dashboard-title"
                    className="view-enter py-8 sm:py-10"
                >
                    <div className="flex flex-col gap-2 border-l-4 border-[var(--primary)] pl-4 sm:pl-6">
                        <p className="eyebrow">
                            Resumen financiero
                        </p>

                        <h1
                            id="dashboard-title"
                            className="max-w-3xl text-3xl font-semibold tracking-[-0.035em] sm:text-4xl"
                        >
                            Resumen de tu dinero
                        </h1>

                        <p className="max-w-2xl text-base leading-7 text-[var(--text-secondary)]">
                            Revisa tu saldo consolidado y cómo está
                            distribuido entre tus bolsillos.
                        </p>
                    </div>

                    {loadError !== null && (
                        <div
                            role="alert"
                            className="status-message status-error mt-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <p>{loadError}</p>

                            <button
                                type="button"
                                onClick={() => void loadSummary()}
                                className="button button-secondary border-[var(--danger)] text-[var(--danger)]"
                            >
                                Reintentar
                            </button>
                        </div>
                    )}

                    <div className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12 lg:grid-rows-[minmax(13rem,auto)_minmax(11rem,auto)]">
                        <article
                            aria-busy={isLoading}
                            className="relative overflow-hidden border border-[var(--charcoal)] bg-[var(--charcoal)] p-6 text-[var(--on-charcoal)] shadow-[6px_6px_0_var(--primary)] sm:p-8 md:col-span-2 lg:col-span-7 lg:row-span-1"
                        >
                            <div
                                aria-hidden="true"
                                className="absolute right-0 top-0 h-2 w-28 bg-[var(--primary)] sm:w-40"
                            />

                            <div className="flex h-full flex-col justify-between gap-10">
                                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                    <div>
                                        <p className="text-sm font-medium text-[var(--on-charcoal)]">
                                            Saldo consolidado
                                        </p>

                                        <p className="mt-1 max-w-md text-sm leading-6 text-[var(--on-charcoal-muted)]">
                                            Suma calculada por Wallet App a partir
                                            de los movimientos de todos tus bolsillos.
                                        </p>
                                    </div>

                                    <span className="w-fit border border-[var(--charcoal-soft)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--on-charcoal)]">
                                        GTQ
                                    </span>
                                </div>

                                <div>
                                    {isLoading ? (
                                        <div role="status" aria-label="Cargando saldo">
                                            <Skeleton
                                                dark
                                                className="h-14 w-full max-w-md sm:h-20"
                                            />
                                        </div>
                                    ) : loadError === null ? (
                                        <p className="data-reveal break-words text-[clamp(2.4rem,7vw,5.5rem)] font-semibold leading-none tracking-[-0.065em] tabular-nums">
                                            {formatCurrency(totalBalance)}
                                        </p>
                                    ) : (
                                        <p className="text-base text-[var(--on-charcoal-muted)]">
                                            Saldo no disponible
                                        </p>
                                    )}
                                </div>
                            </div>
                        </article>

                        <section
                            aria-labelledby="pockets-summary-title"
                            aria-busy={isLoading}
                            className="surface-raised p-6 md:col-span-2 lg:col-span-5 lg:row-span-2"
                        >
                            <div className="flex min-h-11 items-center justify-between gap-4 border-b border-[var(--border)] pb-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--text-secondary)]">
                                        Distribución
                                    </p>

                                    <h2
                                        id="pockets-summary-title"
                                        className="mt-1 text-xl font-semibold tracking-tight"
                                    >
                                        Bolsillos
                                    </h2>
                                </div>

                                <Link
                                    to="/pockets"
                                    aria-label="Ver todos los bolsillos"
                                    className="inline-flex min-h-11 items-center border-b-2 border-[var(--primary)] px-1 text-sm font-semibold text-[var(--primary-hover)] transition-colors hover:border-[var(--text-primary)] hover:text-[var(--text-primary)]"
                                >
                                    Ver todos
                                </Link>
                            </div>

                            <div className="mt-2">
                                {isLoading ? (
                                    <div role="status" aria-label="Cargando bolsillos">
                                        {[0, 1, 2, 3].map((row) => (
                                            <div
                                                key={row}
                                                className="flex min-h-20 items-center justify-between gap-4 border-b border-[var(--border)] py-4 last:border-b-0"
                                            >
                                                <div className="flex-1">
                                                    <Skeleton className="h-4 w-2/3" />
                                                    <Skeleton className="mt-3 h-3 w-20" />
                                                </div>
                                                <Skeleton className="h-5 w-24" />
                                            </div>
                                        ))}
                                    </div>
                                ) : loadError !== null ? (
                                    <p className="py-8 text-sm text-[var(--text-secondary)]">
                                        Los bolsillos no están disponibles.
                                    </p>
                                ) : visiblePockets.length === 0 ? (
                                    <div className="py-8">
                                        <p className="font-semibold">
                                            Aún no tienes bolsillos
                                        </p>

                                        <p className="mt-2 text-sm leading-6 text-[var(--text-secondary)]">
                                            Crea uno para comenzar a organizar tu dinero.
                                        </p>
                                    </div>
                                ) : (
                                    <ul className="data-reveal divide-y divide-[var(--border)]">
                                        {visiblePockets.map((pocket) => (
                                            <li
                                                key={pocket.id}
                                                className="flex min-h-20 items-center justify-between gap-4 py-4"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate font-semibold">
                                                        {pocket.name}
                                                    </p>

                                                    <p className="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--text-secondary)]">
                                                        <span
                                                            aria-hidden="true"
                                                            className={`h-2 w-2 shrink-0 ${pocket.is_active
                                                                ? 'bg-[var(--success)]'
                                                                : 'bg-[var(--text-tertiary)]'}`}
                                                        />
                                                        {pocket.is_active
                                                            ? 'Activo'
                                                            : 'Desactivado'}
                                                    </p>
                                                </div>

                                                <p className="shrink-0 text-right font-semibold tracking-[-0.02em] tabular-nums">
                                                    {formatCurrency(pocket.balance)}
                                                </p>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </section>

                        <section
                            aria-labelledby="actions-title"
                            className="flex flex-col justify-between gap-6 border border-[var(--border-strong)] bg-[var(--primary-soft)] p-6 lg:col-span-4"
                        >
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--primary-hover)]">
                                    Disponible ahora
                                </p>

                                <h2
                                    id="actions-title"
                                    className="mt-2 text-xl font-semibold tracking-tight"
                                >
                                    Organiza tus fondos
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-[var(--text-secondary)]">
                                    Crea, edita, desactiva o reactiva bolsillos
                                    sin perder su historial.
                                </p>
                            </div>

                            <Link
                                to="/pockets"
                                className="button button-primary w-fit"
                            >
                                Gestionar bolsillos
                            </Link>
                        </section>

                        <section
                            aria-labelledby="future-title"
                            className="border border-[var(--border)] bg-[var(--secondary-soft)] p-6 lg:col-span-3"
                        >
                            <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--secondary)]">
                                En desarrollo
                            </p>

                            <h2
                                id="future-title"
                                className="mt-2 text-lg font-semibold tracking-tight"
                            >
                                Más contexto financiero
                            </h2>

                            <ul className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-[var(--text-secondary)]">
                                <li>Movimientos</li>
                                <li>Transferencias</li>
                                <li>Reportes</li>
                                <li>Recurrentes</li>
                            </ul>
                        </section>
                    </div>
                </section>
            </div>
        </main>
    );
}
