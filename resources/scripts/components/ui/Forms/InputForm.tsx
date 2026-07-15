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
    /**
     * Trailing slot on the label row (a hint popover, a "What's this?"). Kept
     * on the label's own line so the control still starts at the same offset as
     * a plain labelled field beside it in a grid.
     */
    labelAction?: ReactNode
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const InputForm = ({
    name,
    label,
    labelAction,
    description,
    formItemProps,
    ...props
}: Props) => {
    return (
        <FormField
            name={name}
            render={({ field, formState }) => (
                <FormItem {...formItemProps}>
                    {label &&
                        // No min-height on the row: it has to collapse to the
                        // label's own line box, or a field with an action sits
                        // a fraction of a pixel below a plain one beside it.
                        (labelAction ? (
                            <div
                                className={
                                    'flex items-center justify-between gap-2'
                                }
                            >
                                <FormLabel>{label}</FormLabel>
                                {labelAction}
                            </div>
                        ) : (
                            <FormLabel>{label}</FormLabel>
                        ))}
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
