import styled from '@emotion/styled'
import { Menu as MantineMenu, MenuItemProps, MenuProps } from '@mantine/core'
import { FC, MouseEventHandler } from 'react'
import tw from 'twin.macro'

interface Menu extends FC<MenuProps> {
    Dropdown: typeof StyledMenuDropdown
    Target: typeof MantineMenu.Target
    Divider: typeof StyledDivider
    Item: typeof StyledMenuItem
}

const StyledMenuDropdown = styled(MantineMenu.Dropdown)`
    &.mantine-Menu-dropdown {
        ${tw`p-2 bg-background border border-accent-200 shadow-lg dark:shadow-none sm:text-sm rounded`}
    }
`

const StyledMenuItem = styled(MantineMenu.Item)<
    MenuItemProps & {
        onClick?: MouseEventHandler<HTMLButtonElement>
        disabled?: boolean
    }
>`
    &.mantine-Menu-item {
        ${tw`w-full text-left px-2 h-9 bg-transparent disabled:text-accent-300 disabled:cursor-not-allowed`}

        ${({ color }) =>
            color === 'red'
                ? tw`text-error sm:hover:bg-error-lighter active:bg-error-lighter sm:active:bg-error-lighter`
                : tw`text-accent-500 sm:hover:bg-accent-200 sm:active:bg-accent-200 active:bg-accent-200`}
    }
`

const StyledDivider = styled(MantineMenu.Divider)`
    ${tw`my-[6px] mx-auto w-[93%] bg-accent-200`}
`

const Menu: Menu = props => <MantineMenu {...props} />

Menu.Dropdown = StyledMenuDropdown

Menu.Target = MantineMenu.Target

Menu.Divider = StyledDivider

Menu.Item = StyledMenuItem

export default Menu
