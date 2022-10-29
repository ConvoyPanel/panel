import { store, useStoreState } from '@/state'
import { StoreProvider } from 'easy-peasy'
import {
  createEmotionCache,
  MantineProvider,
  useMantineColorScheme,
} from '@mantine/core'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import ProgressBar from '@/components/elements/navigation/ProgressBar'
import AuthenticationRouter from '@/routers/AuthenticationRouter'
import ThemeProvider from '@/components/ThemeProvider'

interface ExtendedWindow extends Window {
  ConvoyUser?: {
    name: string
    email: string
    root_admin: boolean
    created_at: string
    updated_at: string
  }
}

const App = () => {
  const { ConvoyUser } = window as ExtendedWindow

  if (ConvoyUser && !store.getState().user.data) {
    store.getActions().user.setUserData({
      name: ConvoyUser.name,
      email: ConvoyUser.email,
      rootAdmin: ConvoyUser.root_admin,
      createdAt: ConvoyUser.created_at,
      updatedAt: ConvoyUser.updated_at,
    })
  }

  if (!store.getState().settings.data) {
    store.getActions().settings.setSettings({
      theme:
        localStorage.theme === 'dark' ||
        (!('theme' in localStorage) &&
          window.matchMedia('(prefers-color-scheme: dark)').matches)
          ? 'dark'
          : 'light',
    })
  }

  return (
    <StoreProvider store={store}>
      <ThemeProvider>
        <ProgressBar />
        <BrowserRouter>
          <Routes>
            <Route path={'/auth/*'} element={<AuthenticationRouter />} />
          </Routes>
        </BrowserRouter>
      </ThemeProvider>
    </StoreProvider>
  )
}

export default App
