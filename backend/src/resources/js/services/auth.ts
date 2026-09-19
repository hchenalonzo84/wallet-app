import type {
    AuthUserResponse,
    LoginCredentials,
    LoginResponse,
    MessageResponse,
} from '../types/auth';

import { httpRequest } from './http';

// Solicita a Sanctum la cookie CSRF necesaria para autenticar la SPA.
async function initializeCsrf(): Promise<void> {
    await httpRequest<void>('/sanctum/csrf-cookie');
}

// Servicio encargado de las operaciones de autenticación web.
export const authService = {
    // Inicia sesión utilizando cookies seguras de Laravel.
    async login(credentials: LoginCredentials): Promise<LoginResponse> {
        await initializeCsrf();

        return httpRequest<LoginResponse>('/login', {
            method: 'POST',
            body: JSON.stringify(credentials),
        });
    },

    // Consulta al usuario asociado con la sesión actual.
    async me(): Promise<AuthUserResponse> {
        return httpRequest<AuthUserResponse>('/api/auth/me');
    },

    // Finaliza la sesión actual del navegador.
    async logout(): Promise<MessageResponse> {
        return httpRequest<MessageResponse>('/logout', {
            method: 'POST',
        });
    },
};