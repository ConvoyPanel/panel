import Radio from '@/components/elements/inputs/Radio'
import { RadioGroupProps } from '@mantine/core'
import { Field as FormikField, FieldProps } from 'formik'
import { forwardRef } from 'react'

const RadioGroupFormik = forwardRef<HTMLInputElement, Omit<RadioGroupProps, 'error'>>(
    ({ name, children, ...props }, ref) => (
            <FormikField ref={ref} name={name}>
                {({ field, meta: { error, touched } }: FieldProps) => (
                    <Radio.Group {...field} {...props} error={touched ? error : undefined}>
                        {children}
                    </Radio.Group>
                )}
            </FormikField>
        )
)

export default RadioGroupFormik
