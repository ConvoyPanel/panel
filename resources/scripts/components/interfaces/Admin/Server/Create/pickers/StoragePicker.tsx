import { useFormContext } from 'react-hook-form'

import useStoragesSWR from '@/api/admin/nodes/storages/use-storages-swr'
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

interface StoragePickerProps {
    nodeId: number | null
}

const StoragePicker = ({ nodeId }: StoragePickerProps) => {
    const { control } = useFormContext()
    const { data: storages, isLoading } = useStoragesSWR(nodeId ?? undefined)

    return (
        <FormField
            control={control}
            name="storageId"
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Storage</FormLabel>
                    <Select
                        onValueChange={field.onChange}
                        defaultValue={field.value}
                        disabled={!nodeId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue placeholder="Select a storage" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {storages?.map(storage => (
                                <SelectItem
                                    key={storage.name}
                                    value={storage.id.toString()}
                                >
                                    {storage.displayName} ({storage.name})
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

export default StoragePicker
