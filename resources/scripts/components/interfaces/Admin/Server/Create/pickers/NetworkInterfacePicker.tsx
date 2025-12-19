import { useFormContext } from 'react-hook-form'

import useNetworkInterfacesSWR from '@/api/admin/nodes/networkInterfaces/use-network-interfaces-swr'
import SimpleEmptyState from '@/components/ui/EmptyStates/SimpleEmptyState'
import { IconNetwork } from '@tabler/icons-react'
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

interface NetworkInterfacePickerProps {
    nodeId: number | null
}

const NetworkInterfacePicker = ({ nodeId }: NetworkInterfacePickerProps) => {
    const { control } = useFormContext()
    const { data, isLoading } = useNetworkInterfacesSWR(nodeId)

    return (
        <FormField
            control={control}
            name="networkInterfaceId"
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Network Interface</FormLabel>
                    <Select
                        onValueChange={field.onChange}
                        defaultValue={field.value}
                        disabled={!nodeId || isLoading}
                    >
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue placeholder="Select a network interface" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {data?.length === 0 ? (
                                <div className="p-2">
                                    <SimpleEmptyState
                                        icon={IconNetwork}
                                        title="No Network Interfaces"
                                        description="There are no network interfaces available for this node."
                                    />
                                </div>
                            ) : (
                                data?.map(item => (
                                    <SelectItem
                                        key={item.id}
                                        value={item.id.toString()}
                                    >
                                        {item.name}
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
