import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import Select, { SelectProps } from '@/components/elements/inputs/Select'

interface Props
    extends Omit<SelectProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const SelectForm = ({ control, ...props }: Props) => {
    const {
        field,
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return <Select {...field} {...props} error={error?.message} />
}

export default SelectForm