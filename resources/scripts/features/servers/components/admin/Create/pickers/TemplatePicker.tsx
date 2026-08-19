import { useTemplates } from '@/features/template-groups/templates/api.ts'
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

interface TemplatePickerProps {
    templateGroupId: string | null
}

const TemplatePicker = ({ templateGroupId }: TemplatePickerProps) => {
    const { control } = useFormContext()
    const { data, isLoading } = useTemplates(templateGroupId, {})
    const items = (data ?? []).map(template => ({
        value: template.uuid,
        label: template.name,
    }))

    return (
        <FormField
            control={control}
            name='templateUuid'
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Template</FormLabel>
                    <Select
                        items={items}
                        onValueChange={field.onChange}
                        // Controlled: the field is also written from outside
                        // the picker (applying a preset), which an
                        // uncontrolled Select would ignore.
                        value={field.value ?? null}
                        disabled={!templateGroupId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger className='w-full'>
                                <SelectValue placeholder='Select a template' />
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

export default TemplatePicker
