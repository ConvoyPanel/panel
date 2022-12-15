
import Textarea, { TextareaProps } from '@/components/elements/inputs/Textarea'
import { Field as FormikField, FieldProps } from 'formik'
import { forwardRef } from 'react'

const TextareaFormik = forwardRef<HTMLInputElement, Omit<TextareaProps, 'error'>>(({ name, ...props }, ref) => (
    <FormikField innerRef={ref} name={name}>
        {({ field, form: { errors, touched } }: FieldProps) => (
            <Textarea {...field} {...props} error={touched[field.name] ? (errors[field.name] as string) : undefined} />
        )}
    </FormikField>
))

export default TextareaFormik
