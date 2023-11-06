import { CheckboxProps } from '@mantine/core'
import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import Checkbox from '@/components/elements/inputs/Checkbox'

interface Props
    extends Omit<CheckboxProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const CheckboxForm = ({ control, ...props }: Props) => {
    const {
        field: { value, ...field },
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return (
        <Checkbox
            checked={value}
            {...field}
            {...props}
            error={error?.message}
        />
    )
}

export default CheckboxForm