import { SelectProps } from '@/components/elements/inputs/Select'
import MultiSelect, { MultiSelectProps } from '@/components/elements/inputs/MultiSelect'
import { useField } from 'formik'

interface Props extends Omit<MultiSelectProps, 'error' | 'onChange'> {
    name: string
}

const MultiSelectFormik = ({ name, ...props }: Props) => {
    const [{ onChange, ...field }, { touched, error }, { setValue }] = useField(name)

    return <MultiSelect onChange={setValue} {...field} {...props} error={touched ? error : undefined} />
}

export default MultiSelectFormik
