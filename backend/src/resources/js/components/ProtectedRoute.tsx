import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';

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
            <main className="flex min-h-screen items-center justify-center bg-slate-950 text-slate-100">
                <p className="text-sm text-slate-400">
                    Comprobando sesión...
                </p>
            </main>
        );
    }

    if (user === null) {
        return <Navigate to="/login" replace />;
    }

    return children;
}