import '../css/app.css';

import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { AppRouter } from './router';

const rootElement = document.getElementById('root');

if (rootElement === null) {
    throw new Error(
        'No se encontró el elemento #root para montar la aplicación React.',
    );
}

createRoot(rootElement).render(
    <StrictMode>
        <AppRouter />
    </StrictMode>,
);