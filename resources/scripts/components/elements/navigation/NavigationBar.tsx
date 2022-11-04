import ContentContainer from '@/components/elements/ContentContainer'
//@ts-ignore
import Logo from '@/assets/images/logo.svg'
import { ActionIcon, LoadingOverlay, Tabs } from '@mantine/core'
import UserDropdown from '@/components/elements/navigation/UserDropdown'
import { useEffect, useMemo, useRef, useState } from 'react'
import NavigationDropdown from '@/components/elements/navigation/NavigationDropdown'
import { useLocation } from 'react-router-dom'
import http from '@/api/http'
import { Bars3Icon, XMarkIcon } from '@heroicons/react/20/solid'

const routes = [
  {
    name: 'Overview',
    path: '/',
    exact: true,
  },
]

const NavigationBar = () => {
  const [isVisible, setIsVisible] = useState(false)
  const [menuVisible, setMenuVisible] = useState(false)
  const [isLoggingOut, setIsLoggingOut] = useState(false)
  const topBar = useRef(null)
  const bottomBar = useRef<HTMLDivElement>(null)
  const placeholder = useRef<HTMLDivElement>(null)
  const logo = useRef<HTMLDivElement>(null)
  const location = useLocation()

  const visibilityObserver = useMemo(
    () =>
      new IntersectionObserver(([entry]) => setIsVisible(entry.isIntersecting)),
    []
  )

  useEffect(() => {
    if (topBar.current) visibilityObserver.observe(topBar.current)
  }, [topBar])

  useEffect(() => {
    const resizeListener = () => {
      let width = window.innerWidth
      || document.documentElement.clientWidth
      || document.body.clientWidth

      if (width < 640) {
        setMenuVisible(false)
      }
    }

    window.addEventListener('resize', resizeListener)

    return () => {
      visibilityObserver.disconnect()
      window.removeEventListener('resize', resizeListener)
    }
  }, [])

  useEffect(() => {
    if (logo.current && bottomBar.current && placeholder.current) {
      if (!isVisible) {
        bottomBar.current.classList.add('fixed', 'top-0', '!shadow-lg')
        logo.current.classList.add('!w-12')
        placeholder.current.classList.remove('hidden')
      } else {
        bottomBar.current.classList.remove('fixed', 'top-0', '!shadow-lg')
        logo.current.classList.remove('!w-12')
        placeholder.current.classList.add('hidden')
      }
    }
  }, [isVisible])

  // get the active route based on the location and if exact
  const activeRoute = useMemo(
    () =>
      routes.find((route) => {
        if (route.exact) {
          return route.path === location.pathname
        } else {
          return location.pathname.startsWith(route.path)
        }
      }),
    [location.pathname]
  )

  const logout = () => {
    setIsLoggingOut(true)
    http.post('/logout').finally(() => {
      window.location.replace('/')
    })
  }

  const Icon = menuVisible ? XMarkIcon : Bars3Icon

  return (
    <div className='bg-white w-full dark:bg-black'>
      <LoadingOverlay visible={isLoggingOut} zIndex={2020} />
      <ContentContainer ref={topBar} className='pt-3 pb-1.5 relative'>
        <div className='flex justify-between'>
          <div className='flex items-center space-x-3'>
            <img src={Logo} className='w-7 h-7 dark:invert' alt='Convoy logo' />
            <h1 className='font-bold text-lg dark:text-white'>Convoy</h1>
          </div>

          <UserDropdown logout={logout} />
          <ActionIcon className='block sm:!hidden' onClick={() => setMenuVisible(!menuVisible)} variant='subtle' size='lg'><Icon className='w-6 h-6' /></ActionIcon>
        </div>
      </ContentContainer>
      <NavigationDropdown logout={logout} visible={menuVisible} />
      <div
        ref={bottomBar}
        className='bg-white pt-1.5 shadow-none transition-shadow dark:bg-black flex w-full dark:border-b border-colors z-[2000]'
      >
        <ContentContainer className='flex w-full'>
          <div
            className='grid place-items-center transition-all w-0 h-full overflow-hidden'
            ref={logo}
            style={{
              transition: 'width 0.25s ease',
            }}
          >
            <img src={Logo} className='w-6 h-6 dark:invert' alt='Convoy logo' />
          </div>
          <Tabs
            value={activeRoute?.name || ''}
            styles={{
              root: {
                overflowX: 'auto',
                '&::-webkit-scrollbar': { display: 'none' },
                scrollbarWidth: 'none',
                marginTop: '0 !important',
              },
              tabsList: {
                flexWrap: 'nowrap',
                borderBottom: '2px solid transparent',
              },
            }}
          >
            <Tabs.List>
              {routes.map((route) => (
                <Tabs.Tab key={route.name} value={route.name}>
                  {route.name}
                </Tabs.Tab>
              ))}
            </Tabs.List>
          </Tabs>
        </ContentContainer>
      </div>
      <div
        ref={placeholder}
        className='hidden h-12 w-full bg-white dark:bg-black'
      />
    </div>
  )
}

export default NavigationBar
