import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import Radio, { RadioGroupProps } from '@/components/elements/inputs/Radio'

interface Props
    extends Omit<RadioGroupProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const RadioGroupForm = ({ control, ...props }: Props) => {
    const {
        field,
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return <Radio.Group {...field} {...props} error={error?.message} />
}

export default RadioGroupForm