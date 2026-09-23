import {
    useState,
    type FormEvent,
} from 'react';

import {
    Link,
    Navigate,
    useNavigate,
} from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';
import { HttpError } from '../services/http';

// Comprueba que el valor pueda tratarse como un objeto indexado.
function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

// Obtiene el primer mensaje útil devuelto por Laravel.
function getErrorMessage(data: unknown): string | null {
    if (!isRecord(data)) {
        return null;
    }

    const errors = data.errors;

    if (isRecord(errors)) {
        for (const value of Object.values(errors)) {
            if (
                Array.isArray(value)
                && typeof value[0] === 'string'
            ) {
                return value[0];
            }
        }
    }

    return typeof data.message === 'string'
        ? data.message
        : null;
}

/**
 * Formulario para crear una nueva cuenta web.
 */
export function RegisterPage() {
    const {
        user,
        isLoading,
        register,
    } = useAuth();

    const navigate = useNavigate();

    const [name, setName] = useState<string>('');
    const [email, setEmail] = useState<string>('');
    const [password, setPassword] = useState<string>('');
    const [passwordConfirmation, setPasswordConfirmation] =
        useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Crea la cuenta e inicia automáticamente la sesión web.
    const handleSubmit = async (
        event: FormEvent<HTMLFormElement>,
    ): Promise<void> => {
        event.preventDefault();

        setErrorMessage(null);
        setIsSubmitting(true);

        try {
            await register({
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });

            navigate('/', {
                replace: true,
            });
        } catch (error: unknown) {
            if (error instanceof HttpError) {
                setErrorMessage(
                    getErrorMessage(error.data)
                    ?? 'No fue posible crear la cuenta.',
                );
            } else {
                setErrorMessage(
                    'No fue posible crear la cuenta. Inténtalo nuevamente.',
                );
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    // Un usuario autenticado no necesita volver a registrarse.
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
                    Crear cuenta
                </h1>

                <p className="mt-3 text-sm leading-6 text-slate-400">
                    Crea tu cuenta para comenzar a administrar tus finanzas.
                </p>

                <form
                    className="mt-8 space-y-5"
                    onSubmit={(event) => void handleSubmit(event)}
                >
                    <div>
                        <label
                            htmlFor="name"
                            className="mb-2 block text-sm font-medium"
                        >
                            Nombre
                        </label>

                        <input
                            id="name"
                            type="text"
                            autoComplete="name"
                            required
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            className="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                        />
                    </div>

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
                            autoComplete="new-password"
                            minLength={8}
                            required
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                        />
                    </div>

                    <div>
                        <label
                            htmlFor="password-confirmation"
                            className="mb-2 block text-sm font-medium"
                        >
                            Confirmar contraseña
                        </label>

                        <input
                            id="password-confirmation"
                            type="password"
                            autoComplete="new-password"
                            minLength={8}
                            required
                            value={passwordConfirmation}
                            onChange={(event) =>
                                setPasswordConfirmation(event.target.value)
                            }
                            className="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none transition focus:border-teal-500"
                        />
                    </div>

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
                            ? 'Creando cuenta...'
                            : 'Crear cuenta'}
                    </button>
                </form>

                <p className="mt-6 text-center text-sm text-slate-400">
                    ¿Ya tienes una cuenta?{' '}
                    <Link
                        to="/login"
                        className="font-medium text-teal-400 hover:text-teal-300"
                    >
                        Iniciar sesión
                    </Link>
                </p>
            </section>
        </main>
    );
}