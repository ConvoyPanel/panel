import { httpErrorToHuman } from '@/api/http'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, {
  NotFound,
  ServerError,
} from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import routes from '@/routers/routes'
import { ServerContext } from '@/state/server'
import { useEffect, useState } from 'react'
import {
  Route,
  Routes,
  useMatch,
} from 'react-router-dom'
import { ArrowPathIcon, NoSymbolIcon } from '@heroicons/react/24/outline'

const navRoutes = [
  {
    name: 'Overview',
    path: '/servers/:id',
  },
]

const ServerRouter = () => {
  const match = useMatch('/servers/:id/*')
  const [error, setError] = useState<string>()
  const server = ServerContext.useStoreState((state) => state.server.data)
  const getServer = ServerContext.useStoreActions(
    (actions) => actions.server.getServer
  )
  const clearServerState = ServerContext.useStoreActions(
    (actions) => actions.clearServerState
  )

  useEffect(() => {
    setError(undefined)

    getServer(match!.params.id as string).catch((error: any) => {
      console.error(error)
      setError(httpErrorToHuman(error))
    })

    return () => {
      clearServerState()
    }
  }, [match?.params.id])

  return (
    <>
      <NavigationBar routes={navRoutes} breadcrumb={server?.name} />
      {!server ? (
        error ? (
          <ServerError message={error} />
        ) : (
          <Spinner />
        )
      ) : (
        <>
          {server.status === 'suspended' && (
            <ScreenBlock center icon={NoSymbolIcon} message='This server is suspended. Contact your provider or system administrator for help.' title='Suspended' />
          )}
          {server.status === 'installing' && (
            <ScreenBlock center icon={ArrowPathIcon} message='Your server is being reinstalled. This can take from 1-15 minutes.' title='Installing' />
          )}
          {server.status === null || server.status === undefined ? (
            <Routes>
              {routes.server.map((route) => (
                <Route
                  key={route.path}
                  path={route.path}
                  element={<route.component />}
                />
              ))}

              <Route path={'*'} element={<NotFound full />} />
            </Routes>
          ) : ''}
        </>
      )}
    </>
  )
}

export default ServerRouter
