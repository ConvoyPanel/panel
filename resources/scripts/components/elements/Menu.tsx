import { Menu as HMenu } from '@headlessui/react'
import { ComponentProps, forwardRef, ReactElement, ReactNode } from 'react'
import { Float } from '@headlessui-float/react'

interface ButtonProps {
    children?: ReactNode
}

interface ItemsProps {
    children: ReactNode
    marginTop?: number | string
}

interface Menu extends React.FC<MenuProps> {
    Button: React.FC<ButtonProps>
    Items: React.FC<ItemsProps>
    Item: React.FC<ItemProps>
    Divider: React.FC
}

interface MenuProps {
    children: ReactElement[]
    className?: string
}

interface ItemProps extends Omit<ComponentProps<'button'>, 'className'> {
    color?: 'accent' | 'danger'
}

const Menu: Menu = ({ children, className }) => {
    return (
        <HMenu as='div' className={className}>
            <Float
                placement='bottom-end'
                flip
                portal
                enter='transition ease-out duration-100'
                enterFrom='opacity-0 -translate-y-[1rem]'
                enterTo='opacity-100 ranslate-y-0'
                leave='ease-in duration-75'
                leaveFrom='opacity-100 translate-y-0'
                leaveTo='opacity-0 -translate-y-[1rem]'
                tailwindcssOriginClass
            >
                {children}
            </Float>
        </HMenu>
    )
}

Menu.Button = forwardRef<HTMLDivElement, ButtonProps>(({ children }, ref) => {
    return (
        <HMenu.Button ref={ref} as='div'>
            {children}
        </HMenu.Button>
    )
})

Menu.Items = forwardRef<HTMLDivElement, ItemsProps>(({ children, marginTop }, ref) => {
    return (
        <HMenu.Items
            ref={ref}
            className='right-0 flex flex-col absolute w-56 p-2 overflow-auto rounded bg-background shadow-lg dark:shadow-none border border-accent-200 focus:outline-none sm:text-sm z-[3000]'
        >
            {children}
        </HMenu.Items>
    )
})

Menu.Item = ({ children, color, disabled, ...props }) => {
    return (
        <HMenu.Item
            as='button'
            className={`w-full text-left px-2 h-12 sm:h-9 bg-transparent ${
                disabled
                    ? 'text-accent-300 cursor-not-allowed'
                    : color === 'danger'
                    ? 'text-error sm:hover:bg-error-lighter active:bg-error-lighter sm:active:bg-error-lighter'
                    : 'text-accent-500 sm:hover:bg-accent-200 sm:active:bg-accent-200 active:bg-accent-200'
            } rounded`}
            disabled={disabled}
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
