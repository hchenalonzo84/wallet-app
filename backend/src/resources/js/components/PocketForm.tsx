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
            className="space-y-6"
            aria-describedby={errorMessage !== null
                ? 'pocket-form-error'
                : undefined}
        >
            <div>
                <label
                    htmlFor="pocket-name"
                    className="field-label"
                >
                    Nombre
                </label>

                <input
                    id="pocket-name"
                    type="text"
                    required
                    maxLength={100}
                    autoFocus
                    disabled={isSubmitting}
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    className="field-control"
                    placeholder="Ej. Ahorros"
                />
            </div>

            <div>
                <label
                    htmlFor="pocket-description"
                    className="field-label"
                >
                    Descripción
                </label>

                <textarea
                    id="pocket-description"
                    maxLength={500}
                    rows={4}
                    disabled={isSubmitting}
                    aria-describedby="pocket-description-count"
                    value={description}
                    onChange={(event) =>
                        setDescription(event.target.value)
                    }
                    className="field-control min-h-28 resize-y"
                    placeholder="Descripción opcional"
                />

                <p
                    id="pocket-description-count"
                    className="field-help text-right tabular-nums"
                >
                    {description.length}/500
                </p>
            </div>

            {errorMessage !== null && (
                <p
                    id="pocket-form-error"
                    role="alert"
                    className="status-message status-error"
                >
                    {errorMessage}
                </p>
            )}

            <div className="flex flex-col-reverse gap-3 border-t border-[var(--border)] pt-6 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    disabled={isSubmitting}
                    onClick={onCancel}
                    className="button button-secondary"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="button button-primary"
                >
                    {isSubmitting
                        ? 'Guardando…'
                        : isEditing
                            ? 'Guardar cambios'
                            : 'Crear bolsillo'}
                </button>
            </div>
        </form>
    );
}
