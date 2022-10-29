import { TextInputProps } from '@mantine/core'
import { forwardRef } from 'react'
import { Field as FormikField, FieldProps } from 'formik'

interface Props extends TextInputProps {
    validate?: (value: any) => undefined | string | Promise<any>;
}

const Field = forwardRef<HTMLInputElement, Props>(({ id, name, label, description, validate, ...props }, ref) => {
  return (
    <FormikField innerRef={ref} name={name} validate={validate}>
        {({ field, form: { errors, touched } }: FieldProps) => (

        )}
    </FormikField>
  )
})
