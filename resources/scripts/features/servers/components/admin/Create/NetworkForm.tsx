import { useFormContext } from 'react-hook-form'
import { useEffect } from 'react'

import NetworkInterfacePicker from '@/features/servers/components/admin/Create/pickers/NetworkInterfacePicker'
import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const NetworkForm = () => {
    const { setValue, watch } = useFormContext()
    const nodeId = watch('nodeId')
    const networkInterfaceId = watch('networkInterfaceId')
    const { data: interfaces } = useNetworkInterfaces(nodeId ? Number(nodeId) : null)
    const selectedInterface = interfaces?.find(
        item => item.id.toString() === networkInterfaceId
    )
    const canOverrideVlan = selectedInterface?.isVlanAware ?? false

    useEffect(() => {
        if (!canOverrideVlan) {
            setValue('vlanTag', '')
        }
    }, [canOverrideVlan, setValue])

    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>Network</Heading>

            <NetworkInterfacePicker nodeId={nodeId ? Number(nodeId) : null} />

            <InputForm
                name={'vlanTag'}
                label={'VLAN tag override'}
                type={'number'}
                min={1}
                max={4094}
                disabled={!canOverrideVlan}
                description={
                    canOverrideVlan
                        ? 'Optional. Leave blank to inherit the selected network interface default.'
                        : 'Select a VLAN-aware network interface to set an override.'
                }
            />

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
