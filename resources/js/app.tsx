// @ts-nocheck
import './bootstrap'
import '../css/app.css'

import React from 'react'
import { render } from 'react-dom'
import { createInertiaApp } from '@inertiajs/inertia-react'
import { InertiaProgress } from '@inertiajs/progress'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { MantineProvider } from '@mantine/core'
import { StoreProvider } from 'easy-peasy'
import { store } from '@/state'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

export const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'Vineyard'

export const queryClient = new QueryClient()

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.tsx`,
      import.meta.glob('./pages/**/*.tsx')
    ),
  setup({ el, App, props }) {
    return render(
      <MantineProvider emotionOptions={{ key: 'mantine', prepend: false }}>
        <StoreProvider store={store}>
          <QueryClientProvider client={queryClient}>
            <App {...props} />
          </QueryClientProvider>
        </StoreProvider>
      </MantineProvider>,
      el
    )
  },
})

InertiaProgress.init({ color: '#4B5563' })
