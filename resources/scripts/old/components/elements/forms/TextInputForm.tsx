import { TextInputProps } from '@mantine/core'
import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import TextInput from '@/components/elements/inputs/TextInput'

interface Props
    extends Omit<TextInputProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const TextInputForm = ({ control, ...props }: Props) => {
    const {
        field,
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return <TextInput {...field} {...props} error={error?.message} />
}

export default TextInputForm