import { cn } from '@/utils'
import { type VariantProps } from 'class-variance-authority'
import { HTMLAttributes, forwardRef, useId } from 'react'

import { fieldVariants } from '@/components/ui/Field'

import { FormItemContext } from './form-item-provider'

type FormItemProps = HTMLAttributes<HTMLDivElement> &
    VariantProps<typeof fieldVariants>

/**
 * A react-hook-form field, laid out by nova's own `Field` variants — `vertical`
 * is label-over-control, `responsive` puts the label and its description beside
 * the control once there is room, which is what a settings row wants. Anything
 * but `vertical` expects the label and description wrapped in a `FieldContent`.
 *
 * `vertical` keeps the classes this component already had rather than nova's,
 * which add `*:w-full`: `CheckboxForm` and `SwitchForm` re-flow the same field
 * into a row and put a checkbox or a switch in it, and a full-width one of
 * those is a stretched control, not a field.
 */
const FormItem = forwardRef<HTMLDivElement, FormItemProps>(
    ({ className, orientation = 'vertical', ...props }, ref) => {
        const id = useId()

        return (
            <FormItemContext.Provider value={{ id }}>
                <div
                    ref={ref}
                    role='group'
                    data-slot='field'
                    data-orientation={orientation}
                    className={cn(
                        orientation === 'vertical'
                            ? 'group/field flex w-full flex-col gap-2'
                            : fieldVariants({ orientation }),
                        className
                    )}
                    {...props}
                />
            </FormItemContext.Provider>
        )
    }
)
FormItem.displayName = 'FormItem'

export default FormItem
