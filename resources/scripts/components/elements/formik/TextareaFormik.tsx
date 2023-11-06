import { FieldProps, Field as FormikField } from 'formik'
import { forwardRef } from 'react'

import Textarea, { TextareaProps } from '@/components/elements/inputs/Textarea'


const TextareaFormik = forwardRef<
    HTMLInputElement,
    Omit<TextareaProps, 'error'>
>(({ name, ...props }, ref) => (
    <FormikField innerRef={ref} name={name}>
        {({ field, meta: { error, touched } }: FieldProps) => (
            <Textarea
                {...field}
                {...props}
                error={touched ? error : undefined}
            />
        )}
    </FormikField>
))

export default TextareaFormik