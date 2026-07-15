import { cn } from '@/utils'
import { HTMLAttributes, ReactNode } from 'react'

import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
} from '@/components/ui/Form'
import { Switch, SwitchProps } from '@/components/ui/Switch'

interface Props extends SwitchProps {
    name: string
    label?: string
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const SwitchForm = ({
    name,
    label,
    description,
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
                        'flex-row items-center justify-between gap-4',
                        formItemProps?.className
                    )}
                >
                    <div className={'flex flex-col gap-0.5 leading-none'}>
                        {label && <FormLabel>{label}</FormLabel>}
                        {description && (
                            <FormDescription>{description}</FormDescription>
                        )}
                    </div>
                    <FormControl>
                        <Switch
                            {...props}
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            disabled={disabled || formState.isSubmitting}
                        />
                    </FormControl>
                </FormItem>
            )}
        />
    )
}

export default SwitchForm
