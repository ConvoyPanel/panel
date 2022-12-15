import TextInput, { TextInputProps } from '@/components/elements/inputs/TextInput'
import { Field as FormikField, FieldProps } from 'formik'
import { forwardRef } from 'react'

const TextInputFormik = forwardRef<HTMLInputElement, Omit<TextInputProps, 'error'>>(({ name, ...props }, ref) => (
    <FormikField innerRef={ref} name={name}>
        {({ field, form: { errors, touched } }: FieldProps) => (
            <TextInput {...field} {...props} error={touched[field.name] ? (errors[field.name] as string) : undefined} />
        )}
    </FormikField>
))

export default TextInputFormik
