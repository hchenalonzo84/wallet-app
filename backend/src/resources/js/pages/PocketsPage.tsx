import {
    useCallback,
    useEffect,
    useRef,
    useState,
} from 'react';

import { AppHeader } from '../components/AppHeader';
import { ConfirmDialog } from '../components/ConfirmDialog';
import { PocketForm } from '../components/PocketForm';
import { Skeleton } from '../components/Skeleton';
import { useDocumentTitle } from '../hooks/useDocumentTitle';
import { HttpError } from '../services/http';
import { pocketService } from '../services/pockets';

import type {
    CreatePocketPayload,
    PocketWithBalance,
} from '../types/pocket';

interface LaravelErrorResponse {
    message?: unknown;
    errors?: unknown;
}

// Obtiene un mensaje legible desde los errores enviados por Laravel.
function getErrorMessage(data: unknown): string | null {
    if (
        typeof data !== 'object'
        || data === null
    ) {
        return null;
    }

    const response = data as LaravelErrorResponse;

    if (
        typeof response.errors === 'object'
        && response.errors !== null
    ) {
        for (const value of Object.values(response.errors)) {
            if (
                Array.isArray(value)
                && typeof value[0] === 'string'
            ) {
                return value[0];
            }
        }
    }

    return typeof response.message === 'string'
        ? response.message
        : null;
}

// Formatea los valores monetarios recibidos desde Laravel.
function formatCurrency(value: string): string {
    const amount = Number(value);

    return new Intl.NumberFormat('es-GT', {
        style: 'currency',
        currency: 'GTQ',
        minimumFractionDigits: 2,
    }).format(amount);
}

/**
 * Administra los bolsillos virtuales del usuario autenticado.
 */
export function PocketsPage() {
    useDocumentTitle('Bolsillos');

    const [pockets, setPockets] = useState<PocketWithBalance[]>([]);
    const [totalBalance, setTotalBalance] = useState<string>('0.00');

    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [processingPocketId, setProcessingPocketId] =
        useState<number | null>(null);

    const [isCreating, setIsCreating] = useState<boolean>(false);
    const [editingPocket, setEditingPocket] =
        useState<PocketWithBalance | null>(null);

    const [confirmingPocket, setConfirmingPocket] =
        useState<PocketWithBalance | null>(null);

    const [pageError, setPageError] = useState<string | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const [dialogError, setDialogError] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const formTriggerRef = useRef<HTMLButtonElement | null>(null);
    const deactivateTriggerRef = useRef<HTMLButtonElement | null>(null);

    // Recarga los bolsillos y sus saldos desde Laravel.
    const loadPockets = useCallback(async (): Promise<void> => {
        setIsLoading(true);

        try {
            setPageError(null);

            const response = await pocketService.getBalances();

            setPockets(response.data);
            setTotalBalance(response.meta.total_balance);
        } catch {
            setPageError(
                'No fue posible cargar los bolsillos.',
            );
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        void loadPockets();
    }, [loadPockets]);

    // Convierte errores HTTP en mensajes para el formulario.
    const handleFormError = (error: unknown): void => {
        if (error instanceof HttpError) {
            setFormError(
                getErrorMessage(error.data)
                ?? 'No fue posible guardar el bolsillo.',
            );

            return;
        }

        setFormError(
            'No fue posible guardar el bolsillo.',
        );
    };

    const handleCreate = async (
        payload: CreatePocketPayload,
    ): Promise<void> => {
        setIsSubmitting(true);
        setFormError(null);
        setSuccessMessage(null);

        try {
            await pocketService.create(payload);

            setIsCreating(false);
            setSuccessMessage('Bolsillo creado correctamente.');

            await loadPockets();
        } catch (error: unknown) {
            handleFormError(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleUpdate = async (
        payload: CreatePocketPayload,
    ): Promise<void> => {
        if (editingPocket === null) {
            return;
        }

        setIsSubmitting(true);
        setFormError(null);
        setSuccessMessage(null);

        try {
            await pocketService.update(
                editingPocket.id,
                payload,
            );

            setEditingPocket(null);
            setSuccessMessage('Cambios guardados correctamente.');

            await loadPockets();
        } catch (error: unknown) {
            handleFormError(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    // Abre el diálogo antes de realizar la desactivación.
    const requestDeactivate = (
        pocket: PocketWithBalance,
        trigger: HTMLButtonElement,
    ): void => {
        deactivateTriggerRef.current = trigger;
        setDialogError(null);
        setConfirmingPocket(pocket);
    };

    // Desactiva el bolsillo únicamente después de confirmar.
    const handleConfirmDeactivate = async (): Promise<void> => {
        if (confirmingPocket === null) {
            return;
        }

        const pocketId = confirmingPocket.id;

        setProcessingPocketId(pocketId);
        setDialogError(null);
        setSuccessMessage(null);

        try {
            await pocketService.deactivate(pocketId);

            await loadPockets();

            setConfirmingPocket(null);
            setSuccessMessage('Bolsillo desactivado. Su historial se conserva.');

            requestAnimationFrame(() => {
                document.getElementById(`pocket-${pocketId}`)?.focus();
            });
        } catch {
            setDialogError(
                'No fue posible desactivar el bolsillo.',
            );
        } finally {
            setProcessingPocketId(null);
        }
    };

    const handleActivate = async (
        pocket: PocketWithBalance,
    ): Promise<void> => {
        setProcessingPocketId(pocket.id);
        setSuccessMessage(null);

        try {
            await pocketService.activate(pocket.id);

            await loadPockets();
            setSuccessMessage('Bolsillo reactivado correctamente.');
        } catch {
            setPageError(
                'No fue posible reactivar el bolsillo.',
            );
        } finally {
            setProcessingPocketId(null);
        }
    };

    const openCreateForm = (trigger: HTMLButtonElement): void => {
        formTriggerRef.current = trigger;
        setEditingPocket(null);
        setFormError(null);
        setIsCreating(true);
    };

    const openEditForm = (
        pocket: PocketWithBalance,
        trigger: HTMLButtonElement,
    ): void => {
        formTriggerRef.current = trigger;
        setIsCreating(false);
        setFormError(null);
        setEditingPocket(pocket);
    };

    const closeForm = (): void => {
        setIsCreating(false);
        setEditingPocket(null);
        setFormError(null);

        requestAnimationFrame(() => {
            formTriggerRef.current?.focus();
        });
    };

    const handleConfirmDialogOpenChange = (
        open: boolean,
    ): void => {
        if (!open) {
            setConfirmingPocket(null);
            setDialogError(null);

            requestAnimationFrame(() => {
                deactivateTriggerRef.current?.focus();
            });
        }
    };

    return (
        <main className="app-shell">
            <div className="mx-auto min-h-screen max-w-7xl px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
                <AppHeader />

                <section
                    id="main-content"
                    tabIndex={-1}
                    aria-labelledby="pockets-title"
                    className="view-enter py-8 sm:py-10"
                >
                    <div className="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                        <div className="border-l-4 border-[var(--primary)] pl-4 sm:pl-6">
                            <p className="eyebrow">
                                Finanzas
                            </p>

                            <h1
                                id="pockets-title"
                                className="mt-2 text-3xl font-semibold tracking-[-0.035em] sm:text-4xl"
                            >
                                Mis bolsillos
                            </h1>

                            <p className="mt-3 max-w-2xl leading-7 text-[var(--text-secondary)]">
                                Organiza tu dinero en bolsillos virtuales.
                                Los saldos se calculan a partir de tus movimientos.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={(event) => openCreateForm(event.currentTarget)}
                            className="button button-primary w-full sm:w-auto"
                        >
                            Crear bolsillo
                        </button>
                    </div>

                    <article
                        aria-busy={isLoading}
                        className="relative mt-8 overflow-hidden border border-[var(--charcoal)] bg-[var(--charcoal)] p-6 text-[var(--on-charcoal)] shadow-[5px_5px_0_var(--primary)] sm:flex sm:items-end sm:justify-between sm:gap-8"
                    >
                        <div>
                            <p className="text-sm font-medium text-[var(--on-charcoal-muted)]">
                                Saldo total en bolsillos
                            </p>

                            {isLoading ? (
                                <Skeleton
                                    dark
                                    className="mt-3 h-10 w-60 max-w-full"
                                />
                            ) : pageError !== null && pockets.length === 0 ? (
                                <p className="mt-2 text-lg font-semibold text-[var(--on-charcoal)]">
                                    Saldo no disponible
                                </p>
                            ) : (
                                <p className="data-reveal mt-2 text-3xl font-semibold tracking-[-0.04em] tabular-nums sm:text-4xl">
                                    {formatCurrency(totalBalance)}
                                </p>
                            )}
                        </div>

                        <p className="mt-4 text-sm text-[var(--on-charcoal-muted)] sm:mt-0 sm:text-right">
                            Calculado desde el libro de movimientos
                        </p>
                    </article>

                    {pageError !== null && (
                        <div
                            role="alert"
                            className="status-message status-error mt-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <p>{pageError}</p>
                            <button
                                type="button"
                                onClick={() => void loadPockets()}
                                className="button button-secondary border-[var(--danger)] text-[var(--danger)]"
                            >
                                Reintentar
                            </button>
                        </div>
                    )}

                    {successMessage !== null && (
                        <p
                            role="status"
                            className="status-message status-success mt-6"
                        >
                            {successMessage}
                        </p>
                    )}

                    {(isCreating || editingPocket !== null) && (
                        <section className="surface-raised view-enter-delay mt-8 p-6 sm:p-8">
                            <p className="eyebrow">
                                {editingPocket !== null
                                    ? 'Ajustar bolsillo'
                                    : 'Nuevo fondo'}
                            </p>

                            <h2 className="mt-2 text-2xl font-semibold tracking-tight">
                                {editingPocket !== null
                                    ? 'Editar bolsillo'
                                    : 'Nuevo bolsillo'}
                            </h2>

                            <div className="mt-6">
                                <PocketForm
                                    key={
                                        editingPocket?.id
                                        ?? 'new-pocket'
                                    }
                                    pocket={editingPocket ?? undefined}
                                    isSubmitting={isSubmitting}
                                    errorMessage={formError}
                                    onSubmit={
                                        editingPocket !== null
                                            ? handleUpdate
                                            : handleCreate
                                    }
                                    onCancel={closeForm}
                                />
                            </div>
                        </section>
                    )}

                    <section
                        aria-labelledby="pocket-list-title"
                        aria-busy={isLoading}
                        className="mt-8"
                    >
                        <div className="mb-4 flex items-end justify-between gap-4">
                            <div>
                                <p className="eyebrow">Distribución</p>
                                <h2
                                    id="pocket-list-title"
                                    className="mt-1 text-2xl font-semibold tracking-tight"
                                >
                                    Todos los bolsillos
                                </h2>
                            </div>

                            {!isLoading && (
                                <p className="text-sm text-[var(--text-secondary)] tabular-nums">
                                    {pockets.length} {pockets.length === 1
                                        ? 'bolsillo'
                                        : 'bolsillos'}
                                </p>
                            )}
                        </div>

                        {isLoading ? (
                            <div
                                role="status"
                                aria-label="Cargando bolsillos"
                                className="surface-raised divide-y divide-[var(--border)]"
                            >
                                {[0, 1, 2, 3].map((row) => (
                                    <div
                                        key={row}
                                        className="grid min-h-32 gap-4 p-6 md:grid-cols-[minmax(0,1fr)_10rem_12rem] md:items-center"
                                    >
                                        <div>
                                            <Skeleton className="h-5 w-40 max-w-full" />
                                            <Skeleton className="mt-3 h-3 w-64 max-w-full" />
                                        </div>
                                        <Skeleton className="h-6 w-28" />
                                        <div className="flex gap-2 md:justify-end">
                                            <Skeleton className="h-11 w-20" />
                                            <Skeleton className="h-11 w-24" />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : pageError !== null && pockets.length === 0 ? (
                            <div className="border border-[var(--border)] bg-[var(--surface)] p-8 text-sm text-[var(--text-secondary)]">
                                Los bolsillos no están disponibles. Usa “Reintentar” para volver a cargarlos.
                            </div>
                        ) : pockets.length === 0 ? (
                            <div className="border border-dashed border-[var(--border-strong)] bg-[var(--surface)] p-10 text-center">
                                <p className="mx-auto grid h-12 w-12 place-items-center bg-[var(--primary-soft)] text-xl font-bold text-[var(--primary)]">
                                    W
                                </p>

                                <h3 className="mt-5 text-lg font-semibold">
                                    Todavía no tienes bolsillos
                                </h3>

                                <p className="mt-2 text-sm leading-6 text-[var(--text-secondary)]">
                                    Crea tu primer bolsillo para comenzar
                                    a organizar tus finanzas.
                                </p>
                            </div>
                        ) : (
                            <div className="surface-raised data-reveal divide-y divide-[var(--border)]">
                                {pockets.map((pocket) => (
                                    <article
                                        key={pocket.id}
                                        id={`pocket-${pocket.id}`}
                                        tabIndex={-1}
                                        className={`grid gap-4 p-6 transition-colors duration-150 md:grid-cols-[minmax(0,1fr)_auto] md:items-center ${pocket.is_active
                                            ? 'hover:bg-[var(--primary-soft)]/40'
                                            : 'bg-[var(--surface-soft)]/65'
                                            }`}
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="min-w-0">
                                                <h3 className="text-lg font-semibold">
                                                    {pocket.name}
                                                </h3>

                                                <span
                                                    className={`mt-2 inline-flex min-h-7 items-center gap-2 px-2.5 text-xs font-semibold ${pocket.is_active
                                                        ? 'bg-[var(--success-soft)] text-[var(--success)]'
                                                        : 'bg-[var(--surface-soft)] text-[var(--text-secondary)]'
                                                        }`}
                                                >
                                                    <span
                                                        aria-hidden="true"
                                                        className={`h-2 w-2 ${pocket.is_active
                                                            ? 'bg-[var(--success)]'
                                                            : 'bg-[var(--text-tertiary)]'}`}
                                                    />
                                                    {pocket.is_active
                                                        ? 'Activo'
                                                        : 'Desactivado'}
                                                </span>
                                            </div>

                                            <p className="shrink-0 text-lg font-semibold tracking-tight tabular-nums text-[var(--text-primary)]">
                                                {formatCurrency(
                                                    pocket.balance,
                                                )}
                                            </p>
                                        </div>

                                        <div className="flex flex-col gap-4 md:items-end">
                                            <p className="max-w-xl text-sm leading-6 text-[var(--text-secondary)] md:self-start">
                                                {pocket.description
                                                    ?? 'Sin descripción.'}
                                            </p>

                                            <div className="flex flex-wrap gap-2 md:justify-end">
                                            <button
                                                type="button"
                                                disabled={
                                                    processingPocketId
                                                    === pocket.id
                                                }
                                                onClick={(event) =>
                                                    openEditForm(
                                                        pocket,
                                                        event.currentTarget,
                                                    )
                                                }
                                                className="button button-secondary"
                                            >
                                                Editar
                                            </button>

                                            {pocket.is_active ? (
                                                <button
                                                    type="button"
                                                    disabled={
                                                        processingPocketId
                                                        === pocket.id
                                                    }
                                                    onClick={(event) =>
                                                        requestDeactivate(
                                                            pocket,
                                                            event.currentTarget,
                                                        )
                                                    }
                                                    className="button button-quiet-danger"
                                                >
                                                    Desactivar
                                                </button>
                                            ) : (
                                                <button
                                                    type="button"
                                                    disabled={
                                                        processingPocketId
                                                        === pocket.id
                                                    }
                                                    onClick={() =>
                                                        void handleActivate(
                                                            pocket,
                                                        )
                                                    }
                                                    className="button border-[var(--success)] text-[var(--success)] hover:bg-[var(--success-soft)]"
                                                >
                                                    {processingPocketId
                                                        === pocket.id
                                                        ? 'Procesando…'
                                                        : 'Reactivar'}
                                                </button>
                                            )}
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </section>
                </section>
            </div>

            <ConfirmDialog
                open={confirmingPocket !== null}
                title="Desactivar bolsillo"
                description={
                    confirmingPocket !== null
                        ? `El bolsillo "${confirmingPocket.name}" dejará de estar disponible para nuevos movimientos, pero conservará todo su historial financiero.`
                        : ''
                }
                confirmLabel="Desactivar"
                processingLabel="Desactivando..."
                isProcessing={
                    confirmingPocket !== null
                    && processingPocketId === confirmingPocket.id
                }
                errorMessage={dialogError}
                onConfirm={handleConfirmDeactivate}
                onOpenChange={handleConfirmDialogOpenChange}
            />
        </main>
    );
}
