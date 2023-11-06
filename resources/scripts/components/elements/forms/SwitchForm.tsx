import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import Switch, { SwitchProps } from '@/components/elements/inputs/Switch'

interface Props
    extends Omit<SwitchProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const SwitchForm = ({ control, ...props }: Props) => {
    const {
        field: { value, ...field },
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return (
        <Switch checked={value} {...field} {...props} error={error?.message} />
    )
}

export default SwitchForm