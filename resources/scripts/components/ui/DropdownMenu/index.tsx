import { Menu as MenuPrimitive } from '@base-ui/react/menu'
import { forwardRef, isValidElement } from 'react'

import DropdownMenuCheckboxItem from './DropdownMenuCheckboxItem'
import DropdownMenuContent from './DropdownMenuContent'
import DropdownMenuItem from './DropdownMenuItem'
import DropdownMenuLabel from './DropdownMenuLabel'
import DropdownMenuRadioItem from './DropdownMenuRadioItem'
import DropdownMenuSeparator from './DropdownMenuSeparator'
import DropdownMenuShortcut from './DropdownMenuShortcut'
import DropdownMenuSubContent from './DropdownMenuSubContent'
import DropdownMenuSubTrigger from './DropdownMenuSubTrigger'

const DropdownMenu = MenuPrimitive.Root
const DropdownMenuGroup = MenuPrimitive.Group
const DropdownMenuPortal = MenuPrimitive.Portal
const DropdownMenuSub = MenuPrimitive.SubmenuRoot
const DropdownMenuRadioGroup = MenuPrimitive.RadioGroup

/**
 * Compatibility wrapper for the existing Radix-style `asChild` call sites.
 * Base UI composes elements through `render`; keeping the small adapter here
 * lets consumers migrate with the shared primitive instead of all at once.
 */
const DropdownMenuTrigger = forwardRef<
    HTMLButtonElement,
    MenuPrimitive.Trigger.Props & { asChild?: boolean }
>(({ asChild, children, ...props }, ref) => (
    <MenuPrimitive.Trigger
        ref={ref}
        data-slot={'dropdown-menu-trigger'}
        render={asChild && isValidElement(children) ? children : undefined}
        {...props}
    >
        {asChild ? undefined : children}
    </MenuPrimitive.Trigger>
))
DropdownMenuTrigger.displayName = 'DropdownMenuTrigger'

export {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuCheckboxItem,
    DropdownMenuRadioItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuGroup,
    DropdownMenuPortal,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuRadioGroup,
}
