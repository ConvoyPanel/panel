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
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/InputGroup'

interface Props extends InputProps {
    name: string
    label?: string
    /**
     * Trailing slot on the label row (a hint popover, a "What's this?"). Kept
     * on the label's own line so the control still starts at the same offset as
     * a plain labelled field beside it in a grid.
     */
    labelAction?: ReactNode
    /**
     * Unit shown inside the field's trailing edge — `MiB`, `MB/s`, `GiB`.
     *
     * A unit belongs to the value, not to the question: "Memory" with a `MiB`
     * suffix says the same thing as "Memory (MiB)" plus a helper line, in one
     * short label and no second line of text.
     */
    suffix?: ReactNode
    description?: ReactNode
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const InputForm = ({
    name,
    label,
    labelAction,
    suffix,
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
                    {suffix ? (
                        /* FormControl wraps the *input*, not the InputGroup:
                           it clones its child to inject the id and aria, and on
                           the wrapper those land on a div, leaving the label
                           associated with nothing. */
                        <InputGroup>
                            <FormControl>
                                <InputGroupInput
                                    {...props}
                                    {...field}
                                    disabled={
                                        props.disabled || formState.isSubmitting
                                    }
                                />
                            </FormControl>
                            <InputGroupAddon align={'inline-end'}>
                                <InputGroupText>{suffix}</InputGroupText>
                            </InputGroupAddon>
                        </InputGroup>
                    ) : (
                        <FormControl>
                            <Input
                                {...props}
                                {...field}
                                disabled={
                                    props.disabled || formState.isSubmitting
                                }
                            />
                        </FormControl>
                    )}
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
