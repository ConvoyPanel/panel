import { useFormContext } from 'react-hook-form'

import useTemplatesSWR from '@/api/admin/templateGroups/templates/use-templates-swr'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'

interface TemplatePickerProps {
    templateGroupId: string | null
}

const TemplatePicker = ({ templateGroupId }: TemplatePickerProps) => {
    const { control } = useFormContext()
    const { data, isLoading } = useTemplatesSWR(templateGroupId, {})

    return (
        <FormField
            control={control}
            name="templateUuid"
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Template</FormLabel>
                    <Select
                        onValueChange={field.onChange}
                        defaultValue={field.value}
                        disabled={!templateGroupId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue placeholder="Select a template" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {data?.map(item => (
                                <SelectItem key={item.uuid} value={item.uuid}>
                                    {item.name}
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
