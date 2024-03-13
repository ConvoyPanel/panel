import { ServerContext } from '@/state/server'
import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'

import Card from '@/components/elements/Card'
import Display from '@/components/elements/displays/DisplayRow'

import { Dd, Dt } from '@/components/dashboard/ServerCard'


const ServerNetworkBlock = () => {
    const server = ServerContext.useStoreState(state => state.server.data!)
    const { t } = useTranslation('server.settings')
    const { t: tStrings } = useTranslation('strings')

    const addresses = useMemo(
        () => [
            ...server.limits.addresses.ipv4,
            ...server.limits.addresses.ipv6,
        ],
        [server.limits.addresses]
    )

    return (
        <Card className='flex flex-col col-span-10 relative'>
            <h5 className='h5'>{tStrings('network')}</h5>
            <div className='flex flex-col space-y-3 mt-3'>
                <dl>
                    <Dt>{tStrings('ip')}</Dt>
                    {addresses.length === 0 ? (
                        <Dd>{t('ip_allocation.empty_state')}</Dd>
                    ) : (
                        <Display.Group className='mt-3'>
                            {addresses.map(ip => (
                                <Display.Row
                                    key={ip.id}
                                    className='grid-cols-1 md:grid-cols-3 text-sm'
                                >
                                    <div className='overflow-hidden'>
                                        <p className='description-small !text-xs'>
                                            {tStrings('address_one')}
                                        </p>
                                        <p className='font-semibold truncate text-foreground overflow-hidden overflow-ellipsis'>
                                            {ip.address}/{ip.cidr}
                                        </p>
                                    </div>
                                    <div>
                                        <p className='description-small !text-xs'>
                                            {tStrings('gateway')}
                                        </p>
                                        <p className='text-foreground font-semibold'>
                                            {ip.gateway}
                                        </p>
                                    </div>
                                    <div>
                                        <p className='description-small !text-xs'>
                                            {tStrings('mac_address')}
                                        </p>
                                        <p className='text-foreground font-semibold'>
                                            {ip.macAddress ?? tStrings('none')}
                                        </p>
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