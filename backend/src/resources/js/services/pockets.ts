import type {
    CreatePocketPayload,
    PocketBalancesResponse,
    PocketResponse,
    UpdatePocketPayload,
} from '../types/pocket';

import { httpRequest } from './http';

// Solicita una cookie CSRF fresca antes de modificar información.
async function initializeCsrf(): Promise<void> {
    await httpRequest<void>('/sanctum/csrf-cookie');
}

// Centraliza las operaciones HTTP relacionadas con bolsillos.
export const pocketService = {
    // Obtiene los bolsillos junto con sus saldos calculados.
    async getBalances(): Promise<PocketBalancesResponse> {
        return httpRequest<PocketBalancesResponse>('/api/balances');
    },

    // Crea un nuevo bolsillo para el usuario autenticado.
    async create(
        payload: CreatePocketPayload,
    ): Promise<PocketResponse> {
        await initializeCsrf();

        return httpRequest<PocketResponse>('/api/pockets', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
    },

    // Modifica uno o varios campos de un bolsillo.
    async update(
        pocketId: number,
        payload: UpdatePocketPayload,
    ): Promise<PocketResponse> {
        await initializeCsrf();

        return httpRequest<PocketResponse>(
            `/api/pockets/${pocketId}`,
            {
                method: 'PATCH',
                body: JSON.stringify(payload),
            },
        );
    },

    // Realiza la desactivación lógica del bolsillo.
    async deactivate(
        pocketId: number,
    ): Promise<PocketResponse> {
        await initializeCsrf();

        return httpRequest<PocketResponse>(
            `/api/pockets/${pocketId}`,
            {
                method: 'DELETE',
            },
        );
    },

    // Reactiva un bolsillo que anteriormente fue desactivado.
    async activate(
        pocketId: number,
    ): Promise<PocketResponse> {
        return pocketService.update(pocketId, {
            is_active: true,
        });
    },
};