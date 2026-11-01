// Representa los datos persistidos de un bolsillo.
export interface Pocket {
    id: number;
    user_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

// Bolsillo acompañado del saldo calculado por Laravel.
export interface PocketWithBalance extends Pocket {
    balance: string;
}

// Datos utilizados para crear un bolsillo.
export interface CreatePocketPayload {
    name: string;
    description: string | null;
}

// Campos que Laravel permite modificar.
export interface UpdatePocketPayload {
    name?: string;
    description?: string | null;
    is_active?: boolean;
}

// Respuesta de operaciones sobre un solo bolsillo.
export interface PocketResponse {
    message: string;
    data: Pocket;
}

// Respuesta del endpoint que calcula todos los saldos.
export interface PocketBalancesResponse {
    data: PocketWithBalance[];
    meta: {
        total_balance: string;
    };
}