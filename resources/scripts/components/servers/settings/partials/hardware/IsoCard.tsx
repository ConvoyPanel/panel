import FormCard from '@/components/elements/FormCard'
import { ServerContext } from '@/state/server'
import useIsosSWR from '@/api/server/settings/useIsosSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import IsoRow from '@/components/servers/settings/partials/hardware/IsoRow'
import { useTranslation } from 'react-i18next'

const IsoCard = () => {
    const { t } = useTranslation('server.settings')
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data } = useIsosSWR(uuid)

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>{t('isos.title')}</FormCard.Title>

                <FlashMessageRender byKey={`servers.${uuid}.settings.hardware.isos`} />

                <div className={'flex flex-col space-y-3 mt-3'}>
                    {data ? (
                        data.length === 0 ? (
                            <p className='text-sm text-center'>{t('isos.empty')}</p>
                        ) : (
                            data.map(iso => <IsoRow iso={iso} key={iso.uuid} />)
                        )
                    ) : null}
                </div>
            </FormCard.Body>
        </FormCard>
    )
}

export default IsoCard
