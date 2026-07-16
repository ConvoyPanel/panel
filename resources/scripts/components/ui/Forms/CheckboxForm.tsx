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
    disabled,
    ...props
}: Props) => {
    return (
        <FormField
            name={name}
            render={({ field, formState }) => (
                <FormItem
                    {...formItemProps}
                    className={cn(
                        'flex-row items-start gap-3 rounded-lg border p-3 shadow-xs',
                        formItemProps?.className
                    )}
                >
                    <FormControl>
                        <Checkbox
                            {...props}
                            // Never pass `undefined`: Base UI latches
                            // controlled-ness on the first render, so a field
                            // whose form has no `defaultValues` would stay
                            // uncontrolled and ignore every later value.
                            checked={field.value ?? false}
                            onCheckedChange={field.onChange}
                            disabled={disabled || formState.isSubmitting}
                        />
                    </FormControl>
                    <div className='flex flex-col gap-1 leading-none'>
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
