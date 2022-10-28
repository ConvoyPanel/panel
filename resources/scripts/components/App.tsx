import { store } from '@/state'
import { StoreProvider } from 'easy-peasy'
import { createEmotionCache, MantineProvider } from '@mantine/core'
import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import ProgressBar from '@/components/elements/navigation/ProgressBar'

const emotionCache = createEmotionCache({
  key: 'mantine',
  prepend: false,
})

const router = createBrowserRouter([
  {
    path: '/',
    element: <div>Hello world!</div>,
  },
])

const App = () => {
  return (
    <StoreProvider store={store}>
      <MantineProvider emotionCache={emotionCache}>
        <ProgressBar />
        <RouterProvider router={router} />
      </MantineProvider>
    </StoreProvider>
  )
}

export default App
