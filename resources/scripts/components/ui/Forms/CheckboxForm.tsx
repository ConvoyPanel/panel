import { cn } from '@/utils'
import { HTMLAttributes, ReactNode } from 'react'

import { Checkbox, CheckboxProps } from '@/components/ui/Checkbox'
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'

interface Props extends CheckboxProps {
    name: string
    label?: string
    description?: ReactNode
    showErrors?: boolean
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const CheckboxForm = ({
    name,
    label,
    description,
    showErrors,
    formItemProps,
    ...props
}: Props) => {
    return (
        <FormField
            name={name}
            render={({ field, formState }) => (
                <FormItem
                    {...formItemProps}
                    className={cn(
                        'flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4 shadow-xs',
                        formItemProps?.className
                    )}
                >
                    <FormControl>
                        <Checkbox
                            {...props}
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            disabled={formState.isSubmitting}
                        />
                    </FormControl>
                    <div className='space-y-1 leading-none'>
                        {label && <FormLabel>{label}</FormLabel>}
                        {description && (
                            <FormDescription>{description}</FormDescription>
                        )}
                        {showErrors && <FormMessage />}
                    </div>
                </FormItem>
            )}
        />
    )
}

export default CheckboxForm
