//@ts-nocheck
import './bootstrap'
import '../css/app.css'

import React from 'react'
import { render } from 'react-dom'
import { createInertiaApp } from '@inertiajs/inertia-react'
import { InertiaProgress } from '@inertiajs/progress'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createEmotionCache, MantineProvider } from '@mantine/core'
import { StoreProvider } from 'easy-peasy'
import { store } from '@/state'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

export const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'Convoy'

export const queryClient = new QueryClient()

const myCache = createEmotionCache({
  key: 'mantine',
  prepend: false
});

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.tsx`,
      import.meta.glob('./pages/**/*.tsx')
    ),
  setup({ el, App, props }) {
    return render(
      <MantineProvider emotionCache={myCache}>
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
