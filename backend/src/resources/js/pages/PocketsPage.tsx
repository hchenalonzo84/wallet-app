import {
    useCallback,
    useEffect,
    useState,
} from 'react';

import { AppHeader } from '../components/AppHeader';
import { ConfirmDialog } from '../components/ConfirmDialog';
import { PocketForm } from '../components/PocketForm';
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

    // Recarga los bolsillos y sus saldos desde Laravel.
    const loadPockets = useCallback(async (): Promise<void> => {
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

        try {
            await pocketService.create(payload);

            setIsCreating(false);

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

        try {
            await pocketService.update(
                editingPocket.id,
                payload,
            );

            setEditingPocket(null);

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
    ): void => {
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

        try {
            await pocketService.deactivate(pocketId);

            await loadPockets();

            setConfirmingPocket(null);
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

        try {
            await pocketService.activate(pocket.id);

            await loadPockets();
        } catch {
            setPageError(
                'No fue posible reactivar el bolsillo.',
            );
        } finally {
            setProcessingPocketId(null);
        }
    };

    const openCreateForm = (): void => {
        setEditingPocket(null);
        setFormError(null);
        setIsCreating(true);
    };

    const openEditForm = (
        pocket: PocketWithBalance,
    ): void => {
        setIsCreating(false);
        setFormError(null);
        setEditingPocket(pocket);
    };

    const closeForm = (): void => {
        setIsCreating(false);
        setEditingPocket(null);
        setFormError(null);
    };

    const handleConfirmDialogOpenChange = (
        open: boolean,
    ): void => {
        if (!open) {
            setConfirmingPocket(null);
            setDialogError(null);
        }
    };

    return (
        <main className="min-h-screen bg-slate-950 text-slate-100">
            <div className="mx-auto min-h-screen max-w-6xl px-6 py-12">
                <AppHeader />

                <section className="py-10">
                    <div className="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-sm font-medium uppercase tracking-[0.2em] text-teal-400">
                                Finanzas
                            </p>

                            <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                                Mis bolsillos
                            </h1>

                            <p className="mt-3 max-w-2xl text-slate-400">
                                Organiza tu dinero en bolsillos virtuales.
                                Los saldos se calculan a partir de tus movimientos.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={openCreateForm}
                            className="rounded-xl bg-teal-500 px-5 py-3 text-sm font-medium text-slate-950 transition hover:bg-teal-400"
                        >
                            + Crear bolsillo
                        </button>
                    </div>

                    <article className="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
                        <p className="text-sm text-slate-400">
                            Saldo total
                        </p>

                        <p className="mt-2 text-3xl font-semibold">
                            {formatCurrency(totalBalance)}
                        </p>
                    </article>

                    {pageError !== null && (
                        <p
                            role="alert"
                            className="mt-6 rounded-xl border border-red-900 bg-red-950/50 px-4 py-3 text-sm text-red-300"
                        >
                            {pageError}
                        </p>
                    )}

                    {(isCreating || editingPocket !== null) && (
                        <section className="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
                            <h2 className="text-xl font-semibold">
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

                    <section className="mt-8">
                        {isLoading ? (
                            <p className="text-slate-400">
                                Cargando bolsillos...
                            </p>
                        ) : pockets.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-slate-700 p-10 text-center">
                                <h2 className="text-lg font-medium">
                                    Todavía no tienes bolsillos
                                </h2>

                                <p className="mt-2 text-sm text-slate-400">
                                    Crea tu primer bolsillo para comenzar
                                    a organizar tus finanzas.
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                                {pockets.map((pocket) => (
                                    <article
                                        key={pocket.id}
                                        className={`rounded-2xl border p-6 transition ${pocket.is_active
                                            ? 'border-slate-800 bg-slate-900'
                                            : 'border-slate-800 bg-slate-900/50 opacity-70'
                                            }`}
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <h2 className="text-lg font-semibold">
                                                    {pocket.name}
                                                </h2>

                                                <span
                                                    className={`mt-2 inline-block rounded-full px-2.5 py-1 text-xs font-medium ${pocket.is_active
                                                        ? 'bg-teal-950 text-teal-300'
                                                        : 'bg-slate-800 text-slate-400'
                                                        }`}
                                                >
                                                    {pocket.is_active
                                                        ? 'Activo'
                                                        : 'Desactivado'}
                                                </span>
                                            </div>

                                            <p className="text-lg font-semibold text-teal-300">
                                                {formatCurrency(
                                                    pocket.balance,
                                                )}
                                            </p>
                                        </div>

                                        <p className="mt-5 min-h-12 text-sm leading-6 text-slate-400">
                                            {pocket.description
                                                ?? 'Sin descripción.'}
                                        </p>

                                        <div className="mt-6 flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                disabled={
                                                    processingPocketId
                                                    === pocket.id
                                                }
                                                onClick={() =>
                                                    openEditForm(pocket)
                                                }
                                                className="rounded-lg border border-slate-700 px-3 py-2 text-sm transition hover:bg-slate-800 disabled:opacity-50"
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
                                                    onClick={() =>
                                                        requestDeactivate(
                                                            pocket,
                                                        )
                                                    }
                                                    className="rounded-lg border border-red-900 px-3 py-2 text-sm text-red-300 transition hover:bg-red-950/50 disabled:opacity-50"
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
                                                    className="rounded-lg border border-teal-800 px-3 py-2 text-sm text-teal-300 transition hover:bg-teal-950/50 disabled:opacity-50"
                                                >
                                                    {processingPocketId
                                                        === pocket.id
                                                        ? 'Procesando...'
                                                        : 'Reactivar'}
                                                </button>
                                            )}
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