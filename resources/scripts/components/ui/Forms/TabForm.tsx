import { Tabs as TabsPrimitive } from '@base-ui/react/tabs'
import { ComponentPropsWithoutRef, HTMLAttributes, ReactNode } from 'react'

import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import { Tabs, TabsList } from '@/components/ui/Tabs'

interface Props extends ComponentPropsWithoutRef<typeof Tabs> {
    name: string
    label?: string
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
    tabsListProps?: TabsPrimitive.List.Props
}

const TabForm = ({
    name,
    label,
    description,
    formItemProps,
    tabsListProps,
    children,
    ...props
}: Props) => {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem {...formItemProps}>
                    <FormControl>
                        <Tabs
                            value={field.value}
                            onValueChange={field.onChange}
                            {...props}
                        >
                            <TabsList {...tabsListProps}>{children}</TabsList>
                        </Tabs>
                    </FormControl>
                    {description && (
                        <FormDescription>{description}</FormDescription>
                    )}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default TabForm
