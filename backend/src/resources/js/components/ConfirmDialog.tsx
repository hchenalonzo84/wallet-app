import * as AlertDialog from '@radix-ui/react-alert-dialog';

interface ConfirmDialogProps {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    processingLabel: string;
    isProcessing: boolean;
    errorMessage?: string | null;
    onConfirm: () => Promise<void>;
    onOpenChange: (open: boolean) => void;
}

/**
 * Diálogo reutilizable para confirmar acciones sensibles.
 */
export function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel,
    processingLabel,
    isProcessing,
    errorMessage = null,
    onConfirm,
    onOpenChange,
}: ConfirmDialogProps) {
    return (
        <AlertDialog.Root
            open={open}
            onOpenChange={(nextOpen) => {
                if (!isProcessing) {
                    onOpenChange(nextOpen);
                }
            }}
        >
            <AlertDialog.Portal>
                <AlertDialog.Overlay className="dialog-overlay" />

                <AlertDialog.Content className="dialog-content p-6 sm:p-8">
                    <div className="flex items-start gap-4">
                        <span
                            aria-hidden="true"
                            className="grid h-10 w-10 shrink-0 place-items-center bg-[var(--danger-soft)] text-lg font-bold text-[var(--danger)]"
                        >
                            !
                        </span>

                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.14em] text-[var(--danger)]">
                                Acción sensible
                            </p>

                            <AlertDialog.Title className="mt-1 text-xl font-semibold tracking-tight text-[var(--text-primary)]">
                                {title}
                            </AlertDialog.Title>
                        </div>
                    </div>

                    <AlertDialog.Description className="mt-5 text-sm leading-6 text-[var(--text-secondary)]">
                        {description}
                    </AlertDialog.Description>

                    {errorMessage !== null && (
                        <p
                            role="alert"
                            className="status-message status-error mt-5"
                        >
                            {errorMessage}
                        </p>
                    )}

                    <div className="mt-7 flex flex-col-reverse gap-3 border-t border-[var(--border)] pt-6 sm:flex-row sm:justify-end">
                        <AlertDialog.Cancel asChild>
                            <button
                                type="button"
                                disabled={isProcessing}
                                className="button button-secondary"
                            >
                                Cancelar
                            </button>
                        </AlertDialog.Cancel>

                        <button
                            type="button"
                            disabled={isProcessing}
                            onClick={() => void onConfirm()}
                            className="button button-danger"
                        >
                            {isProcessing
                                ? processingLabel
                                : confirmLabel}
                        </button>
                    </div>
                </AlertDialog.Content>
            </AlertDialog.Portal>
        </AlertDialog.Root>
    );
}
