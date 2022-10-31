import ContentContainer from '@/components/elements/ContentContainer'
//@ts-ignore
import Logo from '@/assets/images/logo.svg'
import { Tabs } from '@mantine/core'
import UserDropdown from '@/components/elements/navigation/UserDropdown'
import { useEffect, useMemo, useRef, useState } from 'react'

const NavigationBar = () => {
  const [isVisible, setIsVisible] = useState(false)
  const topBar = useRef(null)
  const bottomBar = useRef<HTMLDivElement>(null)
  const placeholder = useRef<HTMLDivElement>(null)
  const logo = useRef<HTMLDivElement>(null)

  const visibilityObserver = useMemo(
    () =>
      new IntersectionObserver(([entry]) => setIsVisible(entry.isIntersecting)),
    []
  )

  useEffect(() => {
    if (topBar.current) visibilityObserver.observe(topBar.current)
  }, [topBar])

  useEffect(() => {
    return () => visibilityObserver.disconnect()
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

  return (
    <div className='bg-white w-full relative dark:bg-black'>
      <ContentContainer ref={topBar} className='pt-3'>
        <div className='flex justify-between'>
          <div className='flex items-center space-x-3'>
            <img src={Logo} className='w-7 h-7 dark:invert' alt='Convoy logo' />
            <h1 className='font-bold text-lg dark:text-white'>Convoy</h1>
          </div>
          <UserDropdown />
        </div>
      </ContentContainer>
      <div
        ref={bottomBar}
        className='bg-white shadow-none transition-shadow dark:bg-black flex w-full dark:border-b border-colors z-10'
      >
        <ContentContainer className='flex w-full'>
          <div
            className='grid place-items-center transition-all w-0 h-full overflow-hidden'
            ref={logo}
            style={{
              transition: 'width 0.25s ease',
            }}
          >
            <img src={Logo} className='w-6 h-6 mt-3 dark:invert' alt='Convoy logo' />
          </div>
          <Tabs
            value='gaming'
            styles={{
              root: {
                overflowX: 'auto',
                '&::-webkit-scrollbar': { display: 'none' },
                scrollbarWidth: 'none',
              },
              tabsList: {
                flexWrap: 'nowrap',
                borderBottom: '2px solid transparent',
              },
            }}
          >
            <Tabs.List>
              <Tabs.Tab value='gaming'>Overview</Tabs.Tab>
              <Tabs.Tab value='fdfs'>Settings</Tabs.Tab>
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
