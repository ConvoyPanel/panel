import { ComponentPropsWithoutRef, HTMLAttributes, ReactNode } from 'react'

import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import { Tabs, TabsList } from '@/components/ui/Tabs'
import { TabsListProps } from '@radix-ui/react-tabs'

interface Props extends ComponentPropsWithoutRef<typeof Tabs> {
    name: string
    label?: string
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
    tabsListProps?: TabsListProps
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
