import { useField } from 'formik'

import Select, { SelectProps } from '@/components/elements/inputs/Select'

interface Props extends Omit<SelectProps, 'error' | 'onChange'> {
    name: string
}

const SelectFormik = ({ name, ...props }: Props) => {
    const [{ onChange, ...field }, { touched, error }, { setValue }] =
        useField(name)

    return (
        <Select
            onChange={setValue}
            {...field}
            {...props}
            error={touched ? error : undefined}
        />
    )
}

export default SelectFormik