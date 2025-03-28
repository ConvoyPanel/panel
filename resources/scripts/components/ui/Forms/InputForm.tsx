import { HTMLAttributes, ReactNode } from 'react'
import { useFormContext } from 'react-hook-form'

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

const InputForm = ({ name, label, description, formItemProps, ...props }: Props) => {
    const { control, formState } = useFormContext()

    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem {...formItemProps}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <Input
                            disabled={formState.isSubmitting}
                            {...props}
                            {...field}
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
