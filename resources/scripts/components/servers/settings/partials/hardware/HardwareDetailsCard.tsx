import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import { useTranslation } from 'react-i18next'

import FormCard from '@/components/elements/FormCard'
import Display from '@/components/elements/displays/DisplayRow'

import { Dd, Dt } from '@/components/dashboard/ServerCard'

const HardwareDetailsCard = () => {
    const { t } = useTranslation('server.settings')
    const { t: tStrings } = useTranslation('strings')
    const server = ServerContext.useStoreState(state => state.server.data!)

    const addresses = [
        ...server.limits.addresses.ipv4,
        ...server.limits.addresses.ipv6,
    ]

    return (
        <>
            <FormCard className='w-full'>
                <FormCard.Body>
                    <FormCard.Title>{tStrings('hardware')}</FormCard.Title>
                    <div className='flex flex-col space-y-3 mt-3'>
                        <div className='flex space-x-10 sm:space-x-12'>
                            <dl>
                                <Dt>{tStrings('cpu')}</Dt>
                                <Dd>{server.limits.cpu}</Dd>
                            </dl>
                            <dl>
                                <Dt>{tStrings('memory')}</Dt>
                                <Dd>{bytesToString(server.limits.memory)}</Dd>
                            </dl>
                            <dl>
                                <Dt>{tStrings('disk')}</Dt>
                                <Dd>{bytesToString(server.limits.disk)}</Dd>
                            </dl>
                        </div>
                        <div className='flex space-x-10 sm:space-x-12'>
                            <dl>
                                <Dt>{t('hardware.bandwidth_used')}</Dt>
                                <Dd>
                                    {bytesToString(server.usages.bandwidth)}
                                </Dd>
                            </dl>
                            <dl>
                                <Dt>{t('hardware.bandwidth_alloted')}</Dt>
                                <Dd>
                                    {server.limits.bandwidth
                                        ? bytesToString(server.limits.bandwidth)
                                        : tStrings('unlimited')}
                                </Dd>
                            </dl>
                        </div>

                        <dl>
                            <Dt>IP Addresses</Dt>
                            {addresses.length === 0 ? (
                                <Dd>{t('ip_allocation.empty_state')}</Dd>
                            ) : (
                                <Display.Group className='mt-3'>
                                    {addresses.map(ip => (
                                        <Display.Row
                                            key={ip.id}
                                            className='grid-cols-1 md:grid-cols-3 text-sm'
                                        >
                                            <div>
                                                <p className='description-small !text-xs'>
                                                    {tStrings('address_one')}
                                                </p>
                                                <p className='font-semibold text-foreground'>
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
                                                    {ip.macAddress ??
                                                        tStrings('none')}
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

export default HardwareDetailsCard
