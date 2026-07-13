import { Storage } from '@/features/nodes/types'
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
    // The form field this picker binds to. Defaults to the create form's
    // primary `storageId`; the secondary-disk field array passes a nested path
    // such as `disks.0.storageId`.
    name?: string
    label?: string
}

const StoragePicker = ({
    nodeId,
    requiredContentTypes,
    name = 'storageId',
    label = 'Storage',
}: StoragePickerProps) => {
    const { control } = useFormContext()
    const { data: storages, isLoading } = useStorages(nodeId ?? undefined)

    const filteredStorages = storages?.filter(storage => {
        if (!requiredContentTypes) return true
        return requiredContentTypes.every(type => storage[type] === true)
    })
    const items = (filteredStorages ?? []).map(storage => {
        const used =
            storage.usages.server + storage.usages.backup + storage.usages.iso
        const free = storage.size - used

        return {
            value: storage.id.toString(),
            label: (
                <div className='flex flex-col gap-1'>
                    <span className='font-medium'>
                        {storage.displayName} ({storage.name})
                    </span>
                    <span className='text-muted-foreground text-xs'>
                        {byteSize(free, { units: 'iec' }).toString()} free out of{' '}
                        {byteSize(storage.size, { units: 'iec' }).toString()}
                    </span>
                </div>
            ),
        }
    })

    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <Select
                        items={items}
                        onValueChange={field.onChange}
                        defaultValue={field.value}
                        disabled={!nodeId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger className='h-auto! w-full text-left *:data-[slot=select-value]:line-clamp-none!'>
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
                                items.map(item => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))
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
