import {
    useState,
    type FormEvent,
} from 'react';

import {
    Navigate,
    useNavigate,
} from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';
import { HttpError } from '../services/http';

interface ErrorResponse {
    message?: string;
}

// Comprueba si una respuesta desconocida contiene un mensaje.
function hasErrorMessage(data: unknown): data is ErrorResponse {
    return (
        typeof data === 'object'
        && data !== null
        && 'message' in data
        && typeof data.message === 'string'
    );
}

/**
 * Formulario de inicio de sesión de la aplicación web.
 */
export function LoginPage() {
    const {
        user,
        isLoading,
        login,
    } = useAuth();

    const navigate = useNavigate();

    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');
    const [remember, setRemember] = useState<boolean>(false);
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Envía las credenciales al backend mediante Sanctum SPA.
    const handleSubmit = async (
        event: FormEvent<HTMLFormElement>,
    ): Promise<void> => {
        event.preventDefault();

        setErrorMessage(null);
        setIsSubmitting(true);

        try {
            await login({
                email,
                password,
                remember,
            });

            navigate('/', {
                replace: true,
            });
        } catch (error: unknown) {
            if (
                error instanceof HttpError
                && hasErrorMessage(error.data)
            ) {
                setErrorMessage(error.data.message ?? null);
            } else {
                setErrorMessage(
                    'No fue posible iniciar sesión. Inténtalo nuevamente.',
                );
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    // Un usuario autenticado no necesita volver al formulario.
    if (!isLoading && user !== null) {
        return <Navigate to="/" replace />;
    }

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-950 px-6 py-12 text-slate-100">
            <section className="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-xl">
                <p className="mb-3 text-sm font-medium uppercase tracking-[0.25em] text-teal-400">
                    Wallet App
                </p>

                <h1 className="text-3xl font-semibold tracking-tight">
                    Iniciar sesión
                </h1>

                <p className="mt-3 text-sm leading-6 text-slate-400">
                    Ingresa tus credenciales para acceder a tus finanzas.
                </p>

                <form
                    className="mt-8 space-y-5"
                    onSubmit={(event) => void handleSubmit(event)}
                >
                    <div>
                        <label
                            htmlFor="email"
                            className="mb-2 block text-sm font-medium"
                        >
                            Correo electrónico
                        </label>

                        <input
                            id="email"
                            type="email"
                            autoComplete="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                        />
                    </div>

                    <div>
                        <label
                            htmlFor="password"
                            className="mb-2 block text-sm font-medium"
                        >
                            Contraseña
                        </label>

                        <input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            required
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                        />
                    </div>

                    <label className="flex items-center gap-3 text-sm text-slate-300">
                        <input
                            type="checkbox"
                            checked={remember}
                            onChange={(event) => setRemember(event.target.checked)}
                            className="h-4 w-4"
                        />

                        Mantener sesión iniciada
                    </label>

                    {errorMessage !== null && (
                        <p
                            role="alert"
                            className="rounded-xl border border-red-900 bg-red-950/50 px-4 py-3 text-sm text-red-300"
                        >
                            {errorMessage}
                        </p>
                    )}

                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full rounded-xl bg-teal-500 px-4 py-3 font-medium text-slate-950 transition hover:bg-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isSubmitting
                            ? 'Iniciando sesión...'
                            : 'Iniciar sesión'}
                    </button>
                </form>
            </section>
        </main>
    );
}