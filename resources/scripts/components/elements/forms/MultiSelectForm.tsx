import { Control, ControllerRenderProps, useController } from 'react-hook-form'

import MultiSelect, {
    MultiSelectProps,
} from '@/components/elements/inputs/MultiSelect'

interface Props
    extends Omit<MultiSelectProps, 'error' | keyof ControllerRenderProps> {
    control?: Control<any, any>
    name: string
}

const MultiSelectForm = ({ control, ...props }: Props) => {
    const {
        field,
        fieldState: { error },
    } = useController({
        name: props.name,
        control: control,
    })

    return <MultiSelect {...field} {...props} error={error?.message} />
}

export default MultiSelectForm
