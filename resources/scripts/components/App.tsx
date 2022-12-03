import { store } from '@/state'
import { StoreProvider } from 'easy-peasy'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import ThemeProvider from '@/components/ThemeProvider'
import { lazy, Suspense } from 'react'
import Spinner from '@/components/elements/Spinner'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import GuestRoutes from '@/routers/middleware/GuestRoutes'
import { NotFound } from '@/components/elements/ScreenBlock'
import { ServerContext } from '@/state/server'
import { NavigationProgress } from '@mantine/nprogress'

interface ExtendedWindow extends Window {
    ConvoyUser?: {
        name: string
        email: string
        root_admin: boolean
        created_at: string
        updated_at: string
    }
}

const AuthenticationRouter = lazy(() => import('@/routers/AuthenticationRouter'))
const DashboardRouter = lazy(() => import('@/routers/DashboardRouter'))
const ServerRouter = lazy(() => import('@/routers/ServerRouter'))

const AdminDashboardRouter = lazy(() => import('@/routers/admin/DashboardRouter'))

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
                (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
                    ? 'dark'
                    : 'light',
        })
    }

    return (
        <StoreProvider store={store}>
            <ThemeProvider>
                <NavigationProgress />
                <BrowserRouter>
                    <Routes>
                        <Route
                            path='/auth/*'
                            element={
                                <GuestRoutes>
                                    <Spinner.Suspense>
                                        <AuthenticationRouter />
                                    </Spinner.Suspense>
                                </GuestRoutes>
                            }
                        />
                        <Route
                            path='/'
                            element={
                                <AuthenticatedRoutes>
                                    <Suspense>
                                        <DashboardRouter />
                                    </Suspense>
                                </AuthenticatedRoutes>
                            }
                        />

                        <Route
                            path='/servers/:id/*'
                            element={
                                <AuthenticatedRoutes>
                                    <Spinner.Suspense>
                                        <ServerContext.Provider>
                                            <ServerRouter />
                                        </ServerContext.Provider>
                                    </Spinner.Suspense>
                                </AuthenticatedRoutes>
                            }
                        />

                        <Route
                            path='/admin/*'
                            element={
                                <AuthenticatedRoutes requireRootAdmin>
                                    <Spinner.Suspense>
                                        <AdminDashboardRouter />
                                    </Spinner.Suspense>
                                </AuthenticatedRoutes>
                            }
                        />

                        <Route path='*' element={<NotFound full />} />
                    </Routes>
                </BrowserRouter>
            </ThemeProvider>
        </StoreProvider>
    )
}

export default App
