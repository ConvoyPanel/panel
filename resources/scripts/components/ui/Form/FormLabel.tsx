import { cn } from '@/utils'
import * as LabelPrimitive from '@radix-ui/react-label'
import { ComponentPropsWithoutRef, ElementRef, forwardRef } from 'react'

import { Label } from '@/components/ui/Label'

import useFormField from './use-form-field'


const FormLabel = forwardRef<
    ElementRef<typeof LabelPrimitive.Root>,
    ComponentPropsWithoutRef<typeof LabelPrimitive.Root>
>(({ className, ...props }, ref) => {
    const { error, formItemId } = useFormField()

    return (
        <Label
            ref={ref}
            className={cn(error && 'text-destructive', className)}
            htmlFor={formItemId}
            {...props}
        />
    )
})
FormLabel.displayName = 'FormLabel'

export default FormLabel
