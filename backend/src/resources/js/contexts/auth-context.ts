import { createContext } from 'react';

import type {
    AuthUser,
    LoginCredentials,
} from '../types/auth';

// Define los datos y acciones disponibles para la autenticación.
export interface AuthContextValue {
    user: AuthUser | null;
    isLoading: boolean;
    login: (credentials: LoginCredentials) => Promise<void>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
}

// El contexto empieza sin proveedor para detectar usos incorrectos.
export const AuthContext = createContext<AuthContextValue | undefined>(
    undefined,
);