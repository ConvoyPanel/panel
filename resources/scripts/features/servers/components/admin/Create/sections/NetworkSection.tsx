import { useNetworkInterfaces } from '@/features/nodes/network-interfaces/api.ts'
import NetworkInterfacePicker from '@/features/servers/components/admin/Create/pickers/NetworkInterfacePicker'
import VlanPicker from '@/features/servers/components/admin/Create/pickers/VlanPicker'
import { useEffect } from 'react'
import { useFormContext, useWatch } from 'react-hook-form'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { FieldFold, InputForm } from '@/components/ui/Forms'

/**
 * The bridge is a real choice — it decides which addresses the server can even
 * be given. The tag and the two counts are almost always taken as they come, so
 * they state themselves instead.
 */
const NetworkSection = () => {
    const { setValue } = useFormContext()
    const nodeId = useWatch({ name: 'nodeId' })
    const networkInterfaceId = useWatch({ name: 'networkInterfaceId' })
    const vlanTag = useWatch({ name: 'vlanTag' })
    const ipv4 = useWatch({ name: 'addressesIpv4Count' })
    const ipv6 = useWatch({ name: 'addressesIpv6Count' })

    const { data: interfaces } = useNetworkInterfaces(
        nodeId ? Number(nodeId) : null
    )
    const selectedInterface = interfaces?.find(
        item => item.id.toString() === networkInterfaceId
    )

    /*
     * Drop a tag the current bridge does not declare. A VLAN is declared per
     * interface, so one carried over from a previously selected bridge is
     * meaningless and would be rejected server-side.
     *
     * This is checked against the bridge's declarations rather than fired
     * blindly whenever the interface changes: a preset applies its bridge and
     * its tag in the same breath, and the blunt version wiped the tag it had
     * just restored.
     */
    useEffect(() => {
        // Nothing to judge against until the node's interfaces have loaded.
        if (!vlanTag || !interfaces) return

        const isDeclared = (selectedInterface?.vlans ?? []).some(
            vlan => String(vlan.tag) === String(vlanTag)
        )

        if (!isDeclared) setValue('vlanTag', '')
    }, [interfaces, selectedInterface, vlanTag, setValue])

    const summary = [
        vlanTag ? `VLAN ${vlanTag}` : 'No VLAN',
        `${Number(ipv4) || 0} IPv4`,
        `${Number(ipv6) || 0} IPv6`,
    ].join(' · ')

    return (
        <Card className={'@container'}>
            <CardHeader>
                <CardTitle>Network</CardTitle>
                <CardDescription>
                    The bridge this server attaches to, and what it is allocated
                    on creation.
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-4'}>
                <NetworkInterfacePicker
                    nodeId={nodeId ? Number(nodeId) : null}
                />

                <FieldFold
                    fields={[
                        'vlanTag',
                        'addressesIpv4Count',
                        'addressesIpv6Count',
                    ]}
                    summary={summary}
                >
                    <div className={'space-y-4'}>
                        <VlanPicker networkInterface={selectedInterface} />

                        <div
                            className={
                                'grid grid-cols-1 gap-3 @2xl:grid-cols-2'
                            }
                        >
                            <InputForm
                                name={'addressesIpv4Count'}
                                label={'IPv4 addresses'}
                                type={'number'}
                            />
                            <InputForm
                                name={'addressesIpv6Count'}
                                label={'IPv6 addresses'}
                                type={'number'}
                            />
                        </div>
                    </div>
                </FieldFold>
            </CardContent>
        </Card>
    )
}

export default NetworkSection
