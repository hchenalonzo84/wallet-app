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
                <AlertDialog.Overlay className="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm" />

                <AlertDialog.Content className="fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-slate-700 bg-slate-900 p-6 text-slate-100 shadow-2xl">
                    <AlertDialog.Title className="text-xl font-semibold">
                        {title}
                    </AlertDialog.Title>

                    <AlertDialog.Description className="mt-3 text-sm leading-6 text-slate-400">
                        {description}
                    </AlertDialog.Description>

                    {errorMessage !== null && (
                        <p
                            role="alert"
                            className="mt-5 rounded-xl border border-red-900 bg-red-950/50 px-4 py-3 text-sm text-red-300"
                        >
                            {errorMessage}
                        </p>
                    )}

                    <div className="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <AlertDialog.Cancel asChild>
                            <button
                                type="button"
                                disabled={isProcessing}
                                className="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Cancelar
                            </button>
                        </AlertDialog.Cancel>

                        <button
                            type="button"
                            disabled={isProcessing}
                            onClick={() => void onConfirm()}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60"
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