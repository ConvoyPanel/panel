import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import NetworkInterfacePicker from '@/features/servers/components/admin/Create/pickers/NetworkInterfacePicker'
import VlanPicker from '@/features/servers/components/admin/Create/pickers/VlanPicker'
import { useEffect } from 'react'
import { useFormContext } from 'react-hook-form'

import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const NetworkForm = () => {
    const { setValue, watch } = useFormContext()
    const nodeId = watch('nodeId')
    const networkInterfaceId = watch('networkInterfaceId')
    const { data: interfaces } = useNetworkInterfaces(
        nodeId ? Number(nodeId) : null
    )
    const selectedInterface = interfaces?.find(
        item => item.id.toString() === networkInterfaceId
    )
    // Clear a stale tag whenever the bridge under it changes: a VLAN is
    // declared per interface, so a tag valid on one is meaningless on another
    // and would be rejected server-side.
    useEffect(() => {
        setValue('vlanTag', '')
    }, [networkInterfaceId, setValue])

    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>Network</Heading>

            <NetworkInterfacePicker nodeId={nodeId ? Number(nodeId) : null} />

            <VlanPicker networkInterface={selectedInterface} />

            <InputForm
                name={'addressesIpv4Count'}
                label={'IPv4 Addresses'}
                type={'number'}
                description={
                    'The number of IPv4 addresses to automatically assign.'
                }
            />

            <InputForm
                name={'addressesIpv6Count'}
                label={'IPv6 Addresses'}
                type={'number'}
                description={
                    'The number of IPv6 addresses to automatically assign.'
                }
            />
        </div>
    )
}

export default NetworkForm
