import { Dd, Dt } from '@/components/dashboard/ServerCard'
import Display from '@/components/elements/displays/DisplayRow'
import FormCard from '@/components/elements/FormCard'
import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import { useMemo } from 'react'

const HardwareDetailsCard = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)

    const addresses = [
        ...server.limits.addresses.ipv4,
        ...server.limits.addresses.ipv6,
    ]

    return (
        <>
            <FormCard className='w-full'>
                    <FormCard.Body>
                        <FormCard.Title>Hardware</FormCard.Title>
                        <div className='flex flex-col space-y-3 mt-3'>
                            <div className='flex space-x-10 sm:space-x-12'>
                                <dl>
                                    <Dt>CPU</Dt>
                                    <Dd>{server.limits.cpu}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Memory</Dt>
                                    <Dd>{bytesToString(server.limits.memory)}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Disk</Dt>
                                    <Dd>{bytesToString(server.limits.disk)}</Dd>
                                </dl>
                            </div>
                            <div className='flex space-x-10 sm:space-x-12'>
                                <dl>
                                    <Dt>Used Bandwidth</Dt>
                                    <Dd>{bytesToString(server.usages.bandwidth)}</Dd>
                                </dl>
                                <dl>
                                    <Dt>Allotted Bandwidth</Dt>
                                    <Dd>
                                        {server.limits.bandwidth ? bytesToString(server.limits.bandwidth) : 'unlimited'}
                                    </Dd>
                                </dl>
                            </div>

                            <dl>
                                <Dt>IP Addresses</Dt>
                                {addresses.length === 0 ? (
                                    <Dd>There are no addresses associated with this server.</Dd>
                                ) : (
                                    <Display.Group className='mt-3'>
                                        {addresses.map(ip => (
                                            <Display.Row key={ip.id} className='grid-cols-1 md:grid-cols-3 text-sm'>
                                                <div>
                                                    <p className='description-small !text-xs'>Address</p>
                                                    <p className='font-semibold text-foreground'>
                                                        {ip.address}/{ip.cidr}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className='description-small !text-xs'>Gateway</p>
                                                    <p className='text-foreground font-semibold'>{ip.gateway}</p>
                                                </div>
                                                <div>
                                                    <p className='description-small !text-xs'>Mac Address</p>
                                                    <p className='text-foreground font-semibold'>
                                                        {ip.macAddress || 'None'}
                                                    </p>
                                                </div>
                                            </Display.Row>
                                        ))}
                                    </Display.Group>
                                )}
                            </dl>
                        </div>
                    </FormCard.Body>
            </FormCard>
        </>
    )
}

export default HardwareDetailsCard;