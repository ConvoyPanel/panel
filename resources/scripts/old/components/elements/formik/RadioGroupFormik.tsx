import { RadioGroupProps } from '@mantine/core'
import { useField } from 'formik'

import Radio from '@/components/elements/inputs/Radio'

interface Props extends Omit<RadioGroupProps, 'error' | 'onChange'> {
    name: string
}

const RadioGroupFormik = ({ name, ...props }: Props) => {
    const [{ onChange, ...field }, { touched, error }, { setValue }] =
        useField(name)

    return (
        <Radio.Group
            onChange={setValue}
            {...field}
            {...props}
            error={touched ? error : undefined}
        />
    )
}

export default RadioGroupFormik