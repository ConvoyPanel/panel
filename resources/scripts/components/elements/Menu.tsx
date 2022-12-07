import { Menu as HMenu, Transition } from '@headlessui/react'
import { ComponentProps, Fragment, ReactNode } from 'react'

interface Menu extends React.FC<MenuProps> {
  Button: React.FC<{
    children?: ReactNode
  }>
  Items: React.FC<{
    children: ReactNode
    marginTop?: number | string
  }>
  Item: React.FC<ItemProps>
  Divider: React.FC
}

interface MenuProps {
  children: ReactNode
  className?: string
}

interface ItemProps extends Omit<ComponentProps<'button'>, 'className'> {
  color?: 'accent' | 'danger'
}

const Menu: Menu = ({ children, className }) => {
  return (
    <HMenu as='div' className={`relative ${className}`}>
      {children}
    </HMenu>
  )
}

Menu.Button = ({ children }) => {
  return <HMenu.Button as={Fragment}>{children}</HMenu.Button>
}

Menu.Items = ({ children, marginTop }) => {
  return (
    <Transition
      as={Fragment}
      enter='transition ease-out duration-100'
      enterFrom='opacity-0 -translate-y-[1rem]'
      enterTo='opacity-100 ranslate-y-0'
      leave='ease-in duration-75'
      leaveFrom='opacity-100 translate-y-0'
      leaveTo='opacity-0 -translate-y-[1rem]'
    >
      {/* TODO: figure out how to automatically change position of menu based on available space */}
      <HMenu.Items style={{
        marginTop: marginTop ?? '0.25rem'
      }} className='right-0 flex flex-col absolute w-56 p-2 overflow-auto rounded bg-background shadow-lg dark:shadow-none border border-accent-200 focus:outline-none sm:text-sm z-[1000]'>
        {children}
      </HMenu.Items>
    </Transition>
  )
}

Menu.Item = ({ children, color, ...props }) => {
  return (
    <HMenu.Item
      as='button'
      className={`w-full text-left px-2 h-12 sm:h-9 disabled:!text-accent-300 bg-transparent disabled:!bg-transparent ${
        color === 'danger'
          ? 'text-error sm:hover:bg-error-lighter active:bg-error-lighter sm:active:bg-error-lighter'
          : 'text-accent-500 sm:hover:bg-accent-200 sm:active:bg-accent-200 active:bg-accent-200'
      } rounded`}
      {...props}
    >
      {children}
    </HMenu.Item>
  )
}

Menu.Divider = () => {
  return <div className='w-full my-1 h-[1px] bg-accent-200' />
}

export default Menu
