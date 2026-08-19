import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import { IconNetwork } from '@tabler/icons-react'
import { useFormContext } from 'react-hook-form'

import SimpleEmptyState from '@/components/ui/EmptyStates/SimpleEmptyState'
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

interface NetworkInterfacePickerProps {
    nodeId: number | null
}

const NetworkInterfacePicker = ({ nodeId }: NetworkInterfacePickerProps) => {
    const { control } = useFormContext()
    const { data, isLoading } = useNetworkInterfaces(nodeId)
    const items = (data ?? []).map(item => {
        const vlanLabel = item.vlanTag
            ? `VLAN ${item.vlanTag}`
            : item.isVlanAware
              ? 'Untagged'
              : 'No VLAN'

        return {
            value: item.id.toString(),
            label: (
                <span className='flex w-full items-center justify-between gap-2'>
                    <span>{item.name}</span>
                    <span className='text-muted-foreground text-xs'>
                        {vlanLabel}
                    </span>
                </span>
            ),
        }
    })

    return (
        <FormField
            control={control}
            name='networkInterfaceId'
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Network Interface</FormLabel>
                    <Select
                        items={items}
                        onValueChange={field.onChange}
                        // Controlled: the field is also written from outside
                        // the picker (applying a preset), which an
                        // uncontrolled Select would ignore.
                        value={field.value ?? null}
                        disabled={!nodeId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger className='w-full'>
                                <SelectValue placeholder='Select a network interface' />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {data?.length === 0 ? (
                                <div className='p-2'>
                                    <SimpleEmptyState
                                        icon={IconNetwork}
                                        title='No Network Interfaces'
                                        description='There are no network interfaces available for this node.'
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

export default NetworkInterfacePicker
