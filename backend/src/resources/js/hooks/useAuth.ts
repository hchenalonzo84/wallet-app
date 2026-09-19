import { useContext } from 'react';

import {
    AuthContext,
    type AuthContextValue,
} from '../contexts/auth-context';

/**
 * Facilita el acceso seguro al estado global de autenticación.
 */
export function useAuth(): AuthContextValue {
    const context = useContext(AuthContext);

    if (context === undefined) {
        throw new Error(
            'useAuth debe utilizarse dentro de AuthProvider.',
        );
    }

    return context;
}