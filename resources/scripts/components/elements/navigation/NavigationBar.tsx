import ContentContainer from '@/components/elements/ContentContainer'
//@ts-ignore
import Logo from '@/assets/images/logo.svg'
import { Tabs } from '@mantine/core'
import UserDropdown from '@/components/elements/navigation/UserDropdown'

const NavigationBar = () => {

  return (
    <div className='bg-white dark:bg-black'>
      <ContentContainer className='pt-3'>
        <div className='flex justify-between'>
          <div className='flex items-center space-x-3'>
            <img src={Logo} className='w-7 h-7 dark:invert' alt='Convoy logo' />
            <h1 className='font-bold text-lg dark:text-white'>Convoy</h1>
          </div>
          <UserDropdown />
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
  )
}

export default NavigationBar
