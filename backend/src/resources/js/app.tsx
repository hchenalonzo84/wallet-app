import '../css/app.css';

import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { AuthProvider } from './providers/AuthProvider';
import { AppRouter } from './router';

const rootElement = document.getElementById('root');

if (rootElement === null) {
    throw new Error(
        'No se encontró el elemento #root para montar la aplicación React.',
    );
}

createRoot(rootElement).render(
    <StrictMode>
        <AuthProvider>
            <AppRouter />
        </AuthProvider>
    </StrictMode>,
);