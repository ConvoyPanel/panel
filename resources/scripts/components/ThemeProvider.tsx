import { useStoreState } from '@/state'
import {
  createEmotionCache,
  MantineProvider,
  useMantineColorScheme,
} from '@mantine/core'
import { ReactNode, useEffect } from 'react'
import colors from 'tailwindcss/colors'

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
        colors: {
          // @ts-ignore there will only be 10 colors in the palette.
          //dark: Object.values(colors.stone)
        }
      }}
    >
      {children}
    </MantineProvider>
  )
}

export default ThemeProvider
