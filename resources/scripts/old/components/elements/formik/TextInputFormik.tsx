import { TextInputProps } from '@mantine/core'
import { FieldProps, Field as FormikField } from 'formik'
import { forwardRef } from 'react'

import TextInput from '@/components/elements/inputs/TextInput'


const TextInputFormik = forwardRef<
    HTMLInputElement,
    Omit<TextInputProps, 'error'>
>(({ name, ...props }, ref) => (
    <FormikField innerRef={ref} name={name}>
        {({ field, meta: { error, touched } }: FieldProps) => (
            <TextInput
                {...field}
                {...props}
                error={touched ? error : undefined}
            />
        )}
    </FormikField>
))

export default TextInputFormik