import { Dd, Dt } from '@/components/dashboard/ServerCard'
import Card from '@/components/elements/Card'
import Display from '@/components/elements/displays/DisplayRow'
import { ServerContext } from '@/state/server'
import { useMemo } from 'react'

const ServerNetworkBlock = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)

    const addresses = useMemo(
        () => [...server.limits.addresses.ipv4, ...server.limits.addresses.ipv6],
        [server.limits.addresses]
    )

    return (
        <Card className='flex flex-col col-span-10 md:col-span-5 relative'>
            <h5 className='h5'>Network</h5>
            <div className='flex flex-col space-y-3 mt-3'>
                <dl>
                    <Dt>IP Addresses</Dt>
                    {addresses.length === 0 ? (
                        <Dd>There are no addresses associated with this server.</Dd>
                    ) : (
                        <Display.Group className='mt-3'>
                            {addresses.map(ip => (
                                <Display.Row key={ip.id} className='grid-cols-1 md:grid-cols-3 text-sm'>
                                    <div className='overflow-hidden'>
                                        <p className='description-small !text-xs'>Address</p>
                                        <p className='font-semibold truncate text-foreground overflow-hidden overflow-ellipsis'>
                                            {ip.address}/{ip.cidr}
                                        </p>
                                    </div>
                                    <div>
                                        <p className='description-small !text-xs'>Gateway</p>
                                        <p className='text-foreground font-semibold'>{ip.gateway}</p>
                                    </div>
                                    <div>
                                        <p className='description-small !text-xs'>Mac Address</p>
                                        <p className='text-foreground font-semibold'>{ip.macAddress || 'None'}</p>
                                    </div>
                                </Display.Row>
                            ))}
                        </Display.Group>
                    )}
                </dl>
            </div>
        </Card>
    )
}

export default ServerNetworkBlock
