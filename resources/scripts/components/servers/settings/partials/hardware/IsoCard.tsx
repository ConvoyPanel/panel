import FormCard from '@/components/elements/FormCard'
import { ServerContext } from '@/state/server'
import useIsosSWR from '@/api/server/settings/useIsosSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import IsoRow from '@/components/servers/settings/partials/hardware/IsoRow'

const IsoCard = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data } = useIsosSWR(uuid)

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Available Media</FormCard.Title>

                <FlashMessageRender byKey='server:settings:hardware:media' />

                <div className={'flex flex-col space-y-3 mt-3'}>
                    {data ? (
                        data.length === 0 ? (
                            <p className='text-sm text-center'>There are no media</p>
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
