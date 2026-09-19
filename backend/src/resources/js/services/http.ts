// Error HTTP tipado que conserva el estado y la respuesta original de Laravel.
export class HttpError extends Error {
    public readonly status: number;
    public readonly data: unknown;

    public constructor(
        status: number,
        message: string,
        data: unknown,
    ) {
        super(message);

        this.name = 'HttpError';
        this.status = status;
        this.data = data;
    }
}

// Busca una cookie del navegador por su nombre.
function getCookie(name: string): string | null {
    const prefix = `${encodeURIComponent(name)}=`;

    const cookie = document.cookie
        .split('; ')
        .find((item: string) => item.startsWith(prefix));

    if (!cookie) {
        return null;
    }

    return decodeURIComponent(cookie.substring(prefix.length));
}

// Determina si una petición puede modificar datos en el servidor.
function requiresCsrfToken(method: string): boolean {
    return !['GET', 'HEAD', 'OPTIONS'].includes(method.toUpperCase());
}

// Ejecuta peticiones HTTP tipadas utilizando las cookies de sesión de Laravel.
export async function httpRequest<T>(
    url: string,
    options: RequestInit = {},
): Promise<T> {
    const method = options.method ?? 'GET';
    const headers = new Headers(options.headers);

    headers.set('Accept', 'application/json');

    if (options.body && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }

    if (requiresCsrfToken(method)) {
        const csrfToken = getCookie('XSRF-TOKEN');

        if (csrfToken) {
            headers.set('X-XSRF-TOKEN', csrfToken);
        }
    }

    const response = await fetch(url, {
        ...options,
        method,
        headers,
        credentials: 'include',
    });

    const contentType = response.headers.get('content-type');
    const hasJson = contentType?.includes('application/json') ?? false;

    const data: unknown = hasJson
        ? await response.json()
        : null;

    if (!response.ok) {
        throw new HttpError(
            response.status,
            `La petición HTTP falló con estado ${response.status}.`,
            data,
        );
    }

    return data as T;
}