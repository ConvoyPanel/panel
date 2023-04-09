import { TextInputProps } from '@mantine/core'
import TextInput from '@/components/elements/inputs/TextInput'
import { WithRequired } from '@/util/helpers'
import { Control, ControllerRenderProps, FieldValues, useController, useFormContext } from 'react-hook-form'
import { ChangeEvent, ReactNode } from 'react'

interface Props extends Omit<TextInputProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const TextInputForm = (props: Props) => {
    const {
        field,
        fieldState: { error },
    } = useController({
        name: props.name,
        control: props.control,
    })

    return <TextInput {...field} {...props} error={error?.message} />
}

export default TextInputForm
