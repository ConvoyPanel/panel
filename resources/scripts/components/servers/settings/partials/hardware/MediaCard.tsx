import FormCard from '@/components/elements/FormCard'
import { ServerContext } from '@/state/server'
import useMediaSWR from '@/api/server/settings/useMediaSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MediaRow from '@/components/servers/settings/partials/hardware/MediaRow'

const MediaCard = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data } = useMediaSWR(uuid)

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
                            data.map(iso => <MediaRow media={iso} key={iso.uuid} />)
                        )
                    ) : null}
                </div>
            </FormCard.Body>
        </FormCard>
    )
}

export default MediaCard
