import FormCard from '@/components/elements/FormCard'
import Button from '@/components/elements/Button'
import useSWR from 'swr'
import { ServerContext } from '@/state/server'
import getMedia from '@/api/server/settings/getMedia'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
import { bytesToString } from '@/util/helpers'
import { useMemo } from 'react'
import useMediaSWR from '@/api/server/settings/useMediaSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MediaRow from '@/components/servers/settings/MediaRow'

const MediaContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data } = useMediaSWR(uuid)

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Available Media</FormCard.Title>

                <FlashMessageRender byKey='server:settings:hardware:media' />

                <div className={'flex flex-col space-y-3 mt-3'}>
                    {data?.map(iso => (
                        <MediaRow media={iso} key={iso.uuid} />
                    ))}
                </div>
            </FormCard.Body>
        </FormCard>
    )
}

export default MediaContainer
