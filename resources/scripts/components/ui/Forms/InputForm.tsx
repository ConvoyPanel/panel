import { HTMLAttributes, ReactNode } from 'react'

import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { Input, InputProps } from '@/components/ui/Input'

interface Props extends InputProps {
    name: string
    label?: string
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const InputForm = ({
    name,
    label,
    description,
    formItemProps,
    ...props
}: Props) => {
    return (
        <FormField
            name={name}
            render={({ field, formState }) => (
                <FormItem {...formItemProps}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <Input
                            {...props}
                            {...field}
                            disabled={props.disabled || formState.isSubmitting}
                        />
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

export default InputForm
