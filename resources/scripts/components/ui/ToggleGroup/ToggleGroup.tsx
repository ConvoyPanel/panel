import { Toggle as TogglePrimitive } from '@base-ui/react/toggle'
import { ToggleGroup as ToggleGroupPrimitive } from '@base-ui/react/toggle-group'
import { type VariantProps } from 'class-variance-authority'
import { CSSProperties, createContext, useContext } from 'react'

import { cn } from '@/utils'

import { toggleVariants } from '@/components/ui/Toggle'

// Values from the shadcn create-page default (base + style "nova"), source
// apps/v4/styles/base-nova/ui/toggle-group.tsx. `spacing={0}` collapses the gap
// and rounds only the outer edges, which is the connected segmented look.

interface ToggleGroupContextValue extends VariantProps<typeof toggleVariants> {
    spacing?: number
}

const ToggleGroupContext = createContext<ToggleGroupContextValue>({
    size: 'default',
    variant: 'default',
    spacing: 2,
})

export interface ToggleGroupProps
    extends ToggleGroupPrimitive.Props,
        VariantProps<typeof toggleVariants> {
    spacing?: number
}

const ToggleGroup = ({
    className,
    variant,
    size,
    spacing = 2,
    orientation = 'horizontal',
    children,
    ...props
}: ToggleGroupProps) => (
    <ToggleGroupPrimitive
        data-slot='toggle-group'
        data-variant={variant}
        data-size={size}
        data-spacing={spacing}
        orientation={orientation}
        style={{ '--gap': spacing } as CSSProperties}
        className={cn(
            'group/toggle-group flex w-fit flex-row items-center gap-[--spacing(var(--gap))] rounded-lg data-[size=sm]:rounded-[min(var(--radius-md),10px)] data-[orientation=vertical]:flex-col data-[orientation=vertical]:items-stretch',
            className
        )}
        {...props}
    >
        <ToggleGroupContext.Provider value={{ variant, size, spacing }}>
            {children}
        </ToggleGroupContext.Provider>
    </ToggleGroupPrimitive>
)

export interface ToggleGroupItemProps
    extends TogglePrimitive.Props,
        VariantProps<typeof toggleVariants> {}

const ToggleGroupItem = ({
    className,
    children,
    variant = 'default',
    size = 'default',
    ...props
}: ToggleGroupItemProps) => {
    const context = useContext(ToggleGroupContext)

    return (
        <TogglePrimitive
            data-slot='toggle-group-item'
            data-variant={context.variant || variant}
            data-size={context.size || size}
            data-spacing={context.spacing}
            className={cn(
                // nova's source targets an older Base UI that emitted a bare
                // `data-horizontal`; 1.6.0 emits `data-orientation="horizontal"`,
                // so the orientation variants are keyed off that instead.
                'shrink-0 focus:z-10 focus-visible:z-10 group-data-[spacing=0]/toggle-group:rounded-none group-data-[spacing=0]/toggle-group:px-2 group-data-[spacing=0]/toggle-group:has-data-[icon=inline-end]:pr-1.5 group-data-[spacing=0]/toggle-group:has-data-[icon=inline-start]:pl-1.5 group-data-[orientation=horizontal]/toggle-group:data-[spacing=0]:first:rounded-l-lg group-data-[orientation=horizontal]/toggle-group:data-[spacing=0]:last:rounded-r-lg group-data-[orientation=vertical]/toggle-group:data-[spacing=0]:first:rounded-t-lg group-data-[orientation=vertical]/toggle-group:data-[spacing=0]:last:rounded-b-lg group-data-[orientation=horizontal]/toggle-group:data-[spacing=0]:data-[variant=outline]:border-l-0 group-data-[orientation=horizontal]/toggle-group:data-[spacing=0]:data-[variant=outline]:first:border-l group-data-[orientation=vertical]/toggle-group:data-[spacing=0]:data-[variant=outline]:border-t-0 group-data-[orientation=vertical]/toggle-group:data-[spacing=0]:data-[variant=outline]:first:border-t',
                toggleVariants({
                    variant: context.variant || variant,
                    size: context.size || size,
                }),
                className
            )}
            {...props}
        >
            {children}
        </TogglePrimitive>
    )
}

export { ToggleGroup, ToggleGroupItem }
