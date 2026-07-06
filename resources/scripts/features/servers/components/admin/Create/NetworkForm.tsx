import { useFormContext } from 'react-hook-form'

import NetworkInterfacePicker from '@/features/servers/components/admin/Create/pickers/NetworkInterfacePicker'
import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const NetworkForm = () => {
    const { watch } = useFormContext()
    const nodeId = watch('nodeId')

    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>Network</Heading>

            <NetworkInterfacePicker nodeId={nodeId ? Number(nodeId) : null} />

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
