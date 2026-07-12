import { cn } from '@/utils'
import { HTMLAttributes, forwardRef } from 'react'

import useFormField from './use-form-field.ts'


const FormDescription = forwardRef<
    HTMLParagraphElement,
    HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => {
    const { formDescriptionId } = useFormField()

    return (
        <p
            ref={ref}
            id={formDescriptionId}
            data-slot='field-description'
            className={cn(
                'text-sm leading-normal font-normal text-muted-foreground',
                className
            )}
            {...props}
        />
    )
})
FormDescription.displayName = 'FormDescription'

export default FormDescription
