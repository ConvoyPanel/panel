import { useStoreActions, useStoreState } from '@/state'
import { Switch } from '@mantine/core'
import { MoonIcon, SunIcon } from '@heroicons/react/20/solid'
import { ReactNode } from 'react'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'

interface Props {
  title: string
  description: string
  children?: ReactNode
}

const LoginFormContainer = ({ title, description, children }: Props) => {
  const theme = useStoreState((state) => state.settings.data!.theme)
  const setTheme = useStoreActions((actions) => actions.settings.setTheme)

  return (
    <div className='min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-black'>
      <div className='w-full sm:max-w-md'>
        <FlashMessageRender className='mb-3' />
        <div className='px-6 py-4 sm:bg-white dark:sm:bg-[#111111] sm:shadow-md overflow-hidden sm:rounded-md'>
          <h1 className='text-3xl font-bold dark:text-white'>{title}</h1>
          <p className='dark:text-stone-400 text-stone-500'>{description}</p>
          <div className='mt-3'>
            { children }
          </div>
        </div>
        <div className='flex justify-between items-center px-6 sm:px-0 py-3 w-full'>
          <p className='text-xs text-stone-500'>&copy; 2020 - {new Date().getFullYear()} Performave</p>
          <Switch
            size='md'
            checked={theme === 'dark'}
            onChange={() => setTheme(theme === 'light' ? 'dark' : 'light')}
            onLabel={<MoonIcon className='w-4 h-4' />}
            offLabel={<SunIcon className='w-4 h-4 text-black' />}
          />
        </div>
      </div>
    </div>
  )
}

export default LoginFormContainer
