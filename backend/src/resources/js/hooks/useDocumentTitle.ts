import { useEffect } from 'react';

/** Mantiene un título descriptivo mientras la vista está montada. */
export function useDocumentTitle(title: string): void {
    useEffect(() => {
        const previousTitle = document.title;

        document.title = `${title} | Wallet App`;

        return () => {
            document.title = previousTitle;
        };
    }, [title]);
}
