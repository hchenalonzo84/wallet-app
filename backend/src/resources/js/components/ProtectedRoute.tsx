import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';
import { Skeleton } from './Skeleton';

interface ProtectedRouteProps {
    children: ReactNode;
}

/**
 * Permite acceder únicamente cuando existe una sesión autenticada.
 */
export function ProtectedRoute({
    children,
}: ProtectedRouteProps) {
    const { user, isLoading } = useAuth();

    if (isLoading) {
        return (
            <main
                aria-busy="true"
                className="app-shell flex min-h-dvh items-center justify-center px-4"
            >
                <div
                    role="status"
                    aria-label="Comprobando sesión"
                    className="surface-raised w-full max-w-sm p-6"
                >
                    <div className="flex items-center gap-3">
                        <Skeleton className="h-9 w-9" />
                        <Skeleton className="h-4 w-32" />
                    </div>

                    <Skeleton className="mt-8 h-7 w-3/4" />
                    <Skeleton className="mt-4 h-4 w-full" />
                    <Skeleton className="mt-2 h-4 w-5/6" />
                </div>
            </main>
        );
    }

    if (user === null) {
        return <Navigate to="/login" replace />;
    }

    return children;
}
