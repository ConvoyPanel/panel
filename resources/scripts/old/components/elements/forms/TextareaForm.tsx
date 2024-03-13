import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import Textarea, { TextareaProps } from '@/components/elements/inputs/Textarea'

interface Props
    extends Omit<TextareaProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const TextareaForm = ({ control, ...props }: Props) => {
    const {
        field,
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return <Textarea {...field} {...props} error={error?.message} />
}

export default TextareaForm