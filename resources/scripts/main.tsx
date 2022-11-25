import React from 'react'
import ReactDOM from 'react-dom/client'

import '@/assets/css/tailwind.css'
import '@/assets/css/preflight.css'

import App from '@/components/App'


ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
)
