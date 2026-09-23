import {
    useCallback,
    useEffect,
    useState,
    type ReactNode,
} from 'react';

import { AuthContext } from '../contexts/auth-context';
import { authService } from '../services/auth';
import { HttpError } from '../services/http';

import type {
    AuthUser,
    LoginCredentials,
    RegisterCredentials,
} from '../types/auth';

interface AuthProviderProps {
    children: ReactNode;
}

/**
 * Mantiene el usuario autenticado disponible para toda la aplicación.
 */
export function AuthProvider({
    children,
}: AuthProviderProps) {
    const [user, setUser] = useState<AuthUser | null>(null);
    const [isLoading, setIsLoading] = useState<boolean>(true);

    // Consulta la sesión actual y actualiza el usuario en memoria.
    const refreshUser = useCallback(async (): Promise<void> => {
        try {
            const response = await authService.me();

            setUser(response.user);
        } catch (error: unknown) {
            if (error instanceof HttpError && error.status === 401) {
                setUser(null);

                return;
            }

            throw error;
        }
    }, []);

    // Registra al usuario y conserva la nueva sesión en memoria.
    const register = async (
        credentials: RegisterCredentials,
    ): Promise<void> => {
        const response = await authService.register(credentials);

        setUser(response.user);
    };

    // Inicia sesión y conserva el usuario devuelto por Laravel.
    const login = async (
        credentials: LoginCredentials,
    ): Promise<void> => {
        const response = await authService.login(credentials);

        setUser(response.user);
    };

    // Cierra la sesión y elimina el usuario del estado local.
    const logout = async (): Promise<void> => {
        await authService.logout();

        setUser(null);
    };

    // Comprueba la sesión al cargar por primera vez la aplicación.
    useEffect(() => {
        void refreshUser()
            .catch(() => {
                setUser(null);
            })
            .finally(() => {
                setIsLoading(false);
            });
    }, [refreshUser]);

    return (
        <AuthContext.Provider
            value={{
                user,
                isLoading,
                register,
                login,
                logout,
                refreshUser,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}