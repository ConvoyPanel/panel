import '@/assets/css/preflight.css';
import '@/assets/css/tailwind.css';
import '@/lib/i18n';
import React from 'react';
import ReactDOM from 'react-dom/client';



import App from '@/components/App';


// do not remove the parentheses because they are what lazy-loads these imports
import('@/util/registerCustomYupValidationRules')

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
)