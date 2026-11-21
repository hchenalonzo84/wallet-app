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
    useDocumentTitle('Crear cuenta');

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
        <AuthLayout
            variant="register"
            title="Crear cuenta"
            description="Prepara un espacio privado para organizar tus bolsillos y consultar sus saldos."
            isCheckingSession={isLoading}
            footer={(
                <p>
                    ¿Ya tienes una cuenta?{' '}
                    <Link
                        to="/login"
                        className="inline-flex min-h-11 items-center font-semibold text-[var(--primary-hover)] underline decoration-[var(--primary-light)] decoration-2 underline-offset-4 transition-colors hover:text-[var(--primary)]"
                    >
                        Iniciar sesión
                    </Link>
                </p>
            )}
        >
            <form
                className="space-y-6"
                aria-describedby={errorMessage !== null
                    ? 'register-error'
                    : undefined}
                onSubmit={(event) => void handleSubmit(event)}
            >
                <div>
                    <label htmlFor="name" className="field-label">
                        Nombre
                    </label>

                    <input
                        id="name"
                        type="text"
                        autoComplete="name"
                        required
                        disabled={isSubmitting || isLoading}
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        className="field-control"
                    />
                </div>

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
                        autoComplete="new-password"
                        minLength={8}
                        required
                        disabled={isSubmitting || isLoading}
                        aria-describedby="password-help"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="field-control"
                    />

                    <p id="password-help" className="field-help">
                        Utiliza al menos 8 caracteres.
                    </p>
                </div>

                <div>
                    <label
                        htmlFor="password-confirmation"
                        className="field-label"
                    >
                        Confirmar contraseña
                    </label>

                    <input
                        id="password-confirmation"
                        type="password"
                        autoComplete="new-password"
                        minLength={8}
                        required
                        disabled={isSubmitting || isLoading}
                        value={passwordConfirmation}
                        onChange={(event) =>
                            setPasswordConfirmation(event.target.value)
                        }
                        className="field-control"
                    />
                </div>

                {errorMessage !== null && (
                    <p
                        id="register-error"
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
                        ? 'Creando cuenta…'
                        : 'Crear cuenta'}
                </button>
            </form>
        </AuthLayout>
    );
}
