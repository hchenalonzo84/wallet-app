import {
    useState,
    type FormEvent,
} from 'react';

import {
    Link,
    Navigate,
    useNavigate,
} from 'react-router-dom';

import { AuthLayout } from '../components/AuthLayout';
import { useAuth } from '../hooks/useAuth';
import { useDocumentTitle } from '../hooks/useDocumentTitle';
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
    useDocumentTitle('Iniciar sesión');

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
        <AuthLayout
            variant="login"
            title="Iniciar sesión"
            description="Accede a tu resumen y continúa organizando tus fondos."
            isCheckingSession={isLoading}
            footer={(
                <p>
                    ¿No tienes una cuenta?{' '}
                    <Link
                        to="/register"
                        className="inline-flex min-h-11 items-center font-semibold text-[var(--primary-hover)] underline decoration-[var(--primary-light)] decoration-2 underline-offset-4 transition-colors hover:text-[var(--primary)]"
                    >
                        Crear cuenta
                    </Link>
                </p>
            )}
        >
            <form
                className="space-y-6"
                aria-describedby={errorMessage !== null
                    ? 'login-error'
                    : undefined}
                onSubmit={(event) => void handleSubmit(event)}
            >
                <div>
                    <label htmlFor="email" className="field-label">
                        Correo electrónico
                    </label>

                    <input
                        id="email"
                        type="email"
                        autoComplete="email"
                        required
                        disabled={isSubmitting || isLoading}
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        className="field-control"
                    />
                </div>

                <div>
                    <label htmlFor="password" className="field-label">
                        Contraseña
                    </label>

                    <input
                        id="password"
                        type="password"
                        autoComplete="current-password"
                        required
                        disabled={isSubmitting || isLoading}
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="field-control"
                    />
                </div>

                <label className="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-[var(--text-secondary)]">
                    <input
                        type="checkbox"
                        checked={remember}
                        disabled={isSubmitting || isLoading}
                        onChange={(event) => setRemember(event.target.checked)}
                        className="h-5 w-5 accent-[var(--primary)]"
                    />

                    Mantener sesión iniciada
                </label>

                {errorMessage !== null && (
                    <p
                        id="login-error"
                        role="alert"
                        className="status-message status-error"
                    >
                        {errorMessage}
                    </p>
                )}

                <button
                    type="submit"
                    disabled={isSubmitting || isLoading}
                    className="button button-primary w-full"
                >
                    {isSubmitting
                        ? 'Iniciando sesión…'
                        : 'Iniciar sesión'}
                </button>
            </form>
        </AuthLayout>
    );
}
