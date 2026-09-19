// Representa al usuario autenticado recibido desde Laravel.
export interface AuthUser {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

// Datos enviados desde el formulario de inicio de sesión.
export interface LoginCredentials {
    email: string;
    password: string;
    remember: boolean;
}

// Respuesta utilizada al consultar al usuario autenticado.
export interface AuthUserResponse {
    user: AuthUser;
}

// Respuesta devuelta después de iniciar sesión correctamente.
export interface LoginResponse extends AuthUserResponse {
    message: string;
}

// Respuesta simple utilizada por operaciones como cerrar sesión.
export interface MessageResponse {
    message: string;
}