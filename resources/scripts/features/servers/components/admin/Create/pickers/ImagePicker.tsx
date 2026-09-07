import { useImageDefinitions } from '@/features/images/definitions/api.ts'
import { useFormContext } from 'react-hook-form'

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

interface ImagePickerProps {
    imageGroupId: string | null
}

const ImagePicker = ({ imageGroupId }: ImagePickerProps) => {
    const { control } = useFormContext()
    const { data, isLoading } = useImageDefinitions(imageGroupId, {})
    const items = (data ?? []).map(image => ({
        value: image.uuid,
        label: image.name,
    }))

    return (
        <FormField
            control={control}
            name='imageUuid'
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Version</FormLabel>
                    <Select
                        items={items}
                        onValueChange={field.onChange}
                        // Controlled: the field is also written from outside
                        // the picker (applying a preset), which an
                        // uncontrolled Select would ignore.
                        value={field.value ?? null}
                        disabled={!imageGroupId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger className='w-full'>
                                <SelectValue placeholder='Select a version' />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {items.map(item => (
                                <SelectItem key={item.value} value={item.value}>
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default ImagePicker
