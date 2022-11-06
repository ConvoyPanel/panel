import { useStoreState } from '@/state'
import { createEmotionCache, MantineProvider } from '@mantine/core'
import { ReactNode, useEffect } from 'react'
import { NotificationsProvider } from '@mantine/notifications'

const emotionCache = createEmotionCache({
  key: 'mantine',
  prepend: false,
})

interface Props {
  children: ReactNode
}

const ThemeProvider = ({ children }: Props) => {
  const theme = useStoreState((state) => state.settings.data?.theme)

  useEffect(() => {
    if (theme === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }, [theme])

  return (
    <MantineProvider
      emotionCache={emotionCache}
      theme={{
        colorScheme: theme === 'dark' ? 'dark' : 'light',
      }}
    >
      <NotificationsProvider>{children}</NotificationsProvider>
    </MantineProvider>
  )
}

export default ThemeProvider
