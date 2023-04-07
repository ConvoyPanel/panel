import React from 'react'
import ReactDOM from 'react-dom/client'

import '@/assets/css/tailwind.css'
import '@/assets/css/preflight.css'

// do not remove the parentheses because they are what lazy-loads these imports
import('@/util/registerCustomYupValidationRules')
import '@/lib/i18n'

import App from '@/components/App'

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
)
