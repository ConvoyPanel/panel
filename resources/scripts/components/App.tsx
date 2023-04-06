import { store } from '@/state'
import { StoreProvider } from 'easy-peasy'
import { BrowserRouter, createBrowserRouter, Route, RouterProvider, Routes } from 'react-router-dom'
import ThemeProvider from '@/components/ThemeProvider'
import { NavigationProgress } from '@mantine/nprogress'
import router from '@/routers/router'
import { LaravelReactI18nProvider } from 'laravel-react-i18n'

interface ExtendedWindow extends Window {
    ConvoyUser?: {
        name: string
        email: string
        root_admin: boolean
        created_at: string
        updated_at: string
    }
    SiteConfiguration?: {
        version: string
    }
}

const App = () => {
    const { ConvoyUser, SiteConfiguration } = window as ExtendedWindow

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
                (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
                    ? 'dark'
                    : 'light',
            version: SiteConfiguration!.version,
        })
    }

    return (
        <LaravelReactI18nProvider
            lang={'en'}
            fallbackLang={'en'}
            resolve={async lang => {
                // @ts-ignore
                const langs = import.meta.glob('../../lang/*.json')
                const fn = langs[`/lang/${lang}.json`]

                if (typeof fn === 'function') {
                    return await fn()
                }
            }}
        >
            <StoreProvider store={store}>
                <ThemeProvider>
                    <NavigationProgress />
                    <RouterProvider router={router} />
                </ThemeProvider>
            </StoreProvider>
        </LaravelReactI18nProvider>
    )
}

export default App
