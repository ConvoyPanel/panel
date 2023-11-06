import { useStoreState } from '@/state'
import { MantineProvider, createEmotionCache } from '@mantine/core'
import { NotificationsProvider } from '@mantine/notifications'
import { ReactNode, useEffect } from 'react'

const emotionCache = createEmotionCache({
    key: 'mantine',
    prepend: false,
})

interface Props {
    children: ReactNode
}

const ThemeProvider = ({ children }: Props) => {
    const theme = useStoreState(state => state.settings.data?.theme)

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
                fontFamily: `Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";`,
            }}
        >
            <NotificationsProvider>{children}</NotificationsProvider>
        </MantineProvider>
    )
}

export default ThemeProvider
