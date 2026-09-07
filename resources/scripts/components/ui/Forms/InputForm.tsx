import { type VariantProps } from 'class-variance-authority'
import { HTMLAttributes, ReactNode } from 'react'

import { FieldContent, fieldVariants } from '@/components/ui/Field'
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

interface Props extends InputProps, VariantProps<typeof fieldVariants> {
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
    /**
     * `responsive` turns the field into a settings row — label and description
     * on the left, control on the right, stacking again when narrow. The
     * description stops being a footnote under the input and becomes the thing
     * that explains the setting, so use it with one.
     */
    orientation?: VariantProps<typeof fieldVariants>['orientation']
    formItemProps?: HTMLAttributes<HTMLDivElement>
}

const InputForm = ({
    name,
    label,
    labelAction,
    suffix,
    description,
    orientation = 'vertical',
    formItemProps,
    ...props
}: Props) => {
    const stacked = orientation === 'vertical'

    return (
        <FormField
            name={name}
            render={({ field, formState }) => {
                const labelRow =
                    label &&
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
                    ))

                const control = suffix ? (
                    /* FormControl wraps the *input*, not the InputGroup: it
                       clones its child to inject the id and aria, and on the
                       wrapper those land on a div, leaving the label
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
                            disabled={props.disabled || formState.isSubmitting}
                        />
                    </FormControl>
                )

                return (
                    <FormItem orientation={orientation} {...formItemProps}>
                        {stacked ? (
                            <>
                                {labelRow}
                                {control}
                                {description && (
                                    <FormDescription>
                                        {description}
                                    </FormDescription>
                                )}
                                <FormMessage />
                            </>
                        ) : (
                            <>
                                <FieldContent>
                                    {labelRow}
                                    {description && (
                                        <FormDescription>
                                            {description}
                                        </FormDescription>
                                    )}
                                </FieldContent>
                                {/* The control keeps its own column so a
                                    validation message sits under the input
                                    rather than stretching the row. */}
                                <div
                                    className={
                                        'flex w-full flex-col gap-2 @md/field-group:w-64'
                                    }
                                >
                                    {control}
                                    <FormMessage />
                                </div>
                            </>
                        )}
                    </FormItem>
                )
            }}
        />
    )
}

export default InputForm
