import { HTMLAttributes, ReactNode } from 'react'

import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { Textarea, TextareaProps } from '@/components/ui/Textarea.tsx'

interface Props extends TextareaProps {
    name: string
    label?: string
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const TextareaForm = ({
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
                        <Textarea
                            {...props}
                            {...field}
                            disabled={formState.isSubmitting}
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

export default TextareaForm
