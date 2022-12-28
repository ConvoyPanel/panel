import Checkbox, { CheckboxProps } from '@/components/elements/inputs/Checkbox'
import Textarea, { TextareaProps } from '@/components/elements/inputs/Textarea'
import { Field as FormikField, FieldProps } from 'formik'
import { forwardRef } from 'react'

const CheckboxFormik = forwardRef<HTMLInputElement, Omit<CheckboxProps, 'error'>>(({ name, ...props }, ref) => (
    <FormikField innerRef={ref} name={name}>
        {({ field: {value, ...field}, meta: { error, touched } }: FieldProps) => (
            <Checkbox checked={value} {...field} {...props} error={touched ? error : undefined} />
        )}
    </FormikField>
))

export default CheckboxFormik
