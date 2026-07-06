import { Storage } from '@/types/storage'
import byteSize from 'byte-size'
import { useFormContext } from 'react-hook-form'

import { useStorages } from '@/features/nodes/storages/api.ts'
import SimpleEmptyState from '@/components/ui/EmptyStates/SimpleEmptyState'
import { IconDatabase } from '@tabler/icons-react'
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
    requiredContentTypes?: (keyof Storage)[]
}

const StoragePicker = ({ nodeId, requiredContentTypes }: StoragePickerProps) => {
    const { control } = useFormContext()
    const { data: storages, isLoading } = useStorages(nodeId ?? undefined)

    const filteredStorages = storages?.filter(storage => {
        if (!requiredContentTypes) return true
        return requiredContentTypes.every(type => storage[type] === true)
    })

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
                            <SelectTrigger className="h-auto text-left [&>span]:line-clamp-none">
                                <SelectValue placeholder="Select a storage" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {filteredStorages?.length === 0 ? (
                                <div className="p-2">
                                    <SimpleEmptyState
                                        icon={IconDatabase}
                                        title="No Storages"
                                        description="There are no storages available for this node."
                                    />
                                </div>
                            ) : (
                                filteredStorages?.map(storage => {
                                    const used =
                                        storage.usages.server +
                                        storage.usages.backup +
                                        storage.usages.iso
                                    const free = storage.size - used

                                    return (
                                        <SelectItem
                                            key={storage.name}
                                            value={storage.id.toString()}
                                        >
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {storage.displayName} ({storage.name})
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {byteSize(free, {
                                                        units: 'iec',
                                                    }).toString()}{' '}
                                                    free out of{' '}
                                                    {byteSize(storage.size, {
                                                        units: 'iec',
                                                    }).toString()}
                                                </span>
                                            </div>
                                        </SelectItem>
                                    )
                                })
                            )}
                        </SelectContent>
                    </Select>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default StoragePicker
