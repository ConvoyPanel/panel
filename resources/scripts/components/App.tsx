import { store } from '@/state'
import { StoreProvider } from 'easy-peasy'
import { BrowserRouter, createBrowserRouter, Route, RouterProvider, Routes } from 'react-router-dom'
import ThemeProvider from '@/components/ThemeProvider'
import { lazy, Suspense } from 'react'
import Spinner from '@/components/elements/Spinner'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import GuestRoutes from '@/routers/middleware/GuestRoutes'
import { NotFound } from '@/components/elements/ScreenBlock'
import { ServerContext } from '@/state/server'
import { NavigationProgress } from '@mantine/nprogress'
import routes, { lazyLoad, Route as RouteDefinition } from '@/routers/routes'
import ServerRouter from '@/routers/ServerRouter'

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

const renderRoutes = (routes: RouteDefinition[]) => {
    return routes.map((route, i) => {
        if (!route.children) {
            return <Route key={i} path={route.path} element={<route.component />} />
        } else {
            return (
                <Route key={i} element={<route.component />}>
                    {renderRoutes(route.children)}
                </Route>
            )
        }
    })
}

const router = createBrowserRouter([
    {
        element: (
            <ServerContext.Provider>
                <ServerRouter />
            </ServerContext.Provider>
        ),
        children: [
            {
                path: '/servers/:id/*',
                element: lazyLoad(lazy(() => import('@/components/servers/overview/ServerOverviewContainer'))),
            },
        ],
    },
])

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
        <StoreProvider store={store}>
            <ThemeProvider>
                <NavigationProgress />
                <RouterProvider router={router} />
                {/*<BrowserRouter>
                    <Routes>{renderRoutes(routes)}</Routes>
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

                        <Route
                            path='/admin/nodes/:id/*'
                            element={
                                <AuthenticatedRoutes requireRootAdmin>
                                    <Suspense fallback={<Spinner />}>
                                        <NodeContext.Provider>
                                            <AdminNodeRouter />
                                        </NodeContext.Provider>
                                    </Suspense>
                                </AuthenticatedRoutes>
                            }
                        />

                        <Route
                            path='/admin/servers/:id/*'
                            element={
                                <AuthenticatedRoutes requireRootAdmin>
                                    <Suspense fallback={<Spinner />}>
                                        <AdminServerContext.Provider>
                                            <AdminServerRouter />
                                        </AdminServerContext.Provider>
                                    </Suspense>
                                </AuthenticatedRoutes>
                            }
                        />

                        <Route
                            path='/admin/users/:id/*'
                            element={
                                <AuthenticatedRoutes requireRootAdmin>
                                    <Suspense fallback={<Spinner />}>
                                        <AdminUserContext.Provider>
                                            <AdminUserRouter />
                                        </AdminUserContext.Provider>
                                    </Suspense>
                                </AuthenticatedRoutes>
                            }
                        />

                    <Route path='*' element={<NotFound full />} />
                </BrowserRouter>*/}
            </ThemeProvider>
        </StoreProvider>
    )
}

export default App
