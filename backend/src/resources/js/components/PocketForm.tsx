import {
    useState,
    type FormEvent,
} from 'react';

import type {
    CreatePocketPayload,
    Pocket,
} from '../types/pocket';

interface PocketFormProps {
    pocket?: Pocket;
    isSubmitting: boolean;
    errorMessage: string | null;
    onSubmit: (payload: CreatePocketPayload) => Promise<void>;
    onCancel: () => void;
}

/**
 * Formulario reutilizable para crear o editar bolsillos.
 */
export function PocketForm({
    pocket,
    isSubmitting,
    errorMessage,
    onSubmit,
    onCancel,
}: PocketFormProps) {
    const [name, setName] = useState<string>(
        pocket?.name ?? '',
    );

    const [description, setDescription] = useState<string>(
        pocket?.description ?? '',
    );

    const isEditing = pocket !== undefined;

    const handleSubmit = async (
        event: FormEvent<HTMLFormElement>,
    ): Promise<void> => {
        event.preventDefault();

        const normalizedDescription = description.trim();

        await onSubmit({
            name: name.trim(),
            description:
                normalizedDescription === ''
                    ? null
                    : normalizedDescription,
        });
    };

    return (
        <form
            onSubmit={(event) => void handleSubmit(event)}
            className="space-y-5"
        >
            <div>
                <label
                    htmlFor="pocket-name"
                    className="mb-2 block text-sm font-medium text-slate-200"
                >
                    Nombre
                </label>

                <input
                    id="pocket-name"
                    type="text"
                    required
                    maxLength={100}
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    className="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                    placeholder="Ej. Ahorros"
                />
            </div>

            <div>
                <label
                    htmlFor="pocket-description"
                    className="mb-2 block text-sm font-medium text-slate-200"
                >
                    Descripción
                </label>

                <textarea
                    id="pocket-description"
                    maxLength={500}
                    rows={4}
                    value={description}
                    onChange={(event) =>
                        setDescription(event.target.value)
                    }
                    className="w-full resize-none rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                    placeholder="Descripción opcional"
                />

                <p className="mt-2 text-right text-xs text-slate-500">
                    {description.length}/500
                </p>
            </div>

            {errorMessage !== null && (
                <p
                    role="alert"
                    className="rounded-xl border border-red-900 bg-red-950/50 px-4 py-3 text-sm text-red-300"
                >
                    {errorMessage}
                </p>
            )}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    disabled={isSubmitting}
                    onClick={onCancel}
                    className="rounded-xl border border-slate-700 px-4 py-3 text-sm font-medium transition hover:bg-slate-800 disabled:opacity-60"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="rounded-xl bg-teal-500 px-5 py-3 text-sm font-medium text-slate-950 transition hover:bg-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {isSubmitting
                        ? 'Guardando...'
                        : isEditing
                            ? 'Guardar cambios'
                            : 'Crear bolsillo'}
                </button>
            </div>
        </form>
    );
}