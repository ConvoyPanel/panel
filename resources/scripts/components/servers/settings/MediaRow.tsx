import { EyeSlashIcon } from '@heroicons/react/20/solid'
import { bytesToString } from '@/util/helpers'
import Button from '@/components/elements/Button'
import { Media } from '@/api/server/settings/getMedia'
import useMediaSWR from '@/api/server/settings/useMediaSWR'
import { ServerContext } from '@/state/server'
import useFlash, { useFlashKey } from '@/util/useFlash'
import mountMedia from '@/api/server/settings/mountMedia'
import unmountMedia from '@/api/server/settings/unmountMedia'
import { useState } from 'react'
import useBootOrderSWR from '@/api/server/settings/useBootOrderSWR'

interface Props {
    media: Media
}

const MediaRow = ({ media }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('server:settings:hardware:media')
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { mutate: mutateMedia } = useMediaSWR(uuid)
    const { mutate: mutateBootOrder } = useBootOrderSWR(uuid)
    const [loading, setLoading] = useState(false)

    const handleMount = async () => {
        setLoading(true)
        clearFlashes()
        try {
            await mountMedia(uuid, media.uuid)

            mutateMedia(data => {
                return data?.map(iso => {
                    if (iso.uuid === media.uuid) {
                        return { ...iso, mounted: true }
                    }

                    return iso
                })
            }, false)
            mutateBootOrder()
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
        setLoading(false)
    }

    const handleUnmount = async () => {
        setLoading(true)
        clearFlashes()
        try {
            await unmountMedia(uuid, media.uuid)

            mutateMedia(data => {
                return data?.map(iso => {
                    if (iso.uuid === media.uuid) {
                        return { ...iso, mounted: false }
                    }

                    return iso
                })
            }, false)
            mutateBootOrder()
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
        setLoading(false)
    }

    return (
        <div className={'flex items-center justify-between py-2 px-4 border border-accent-200 rounded'}>
            <div>
                <div className={'flex items-center space-x-3'}>
                    <p className='text-foreground'>{media.name}</p>
                    {media.hidden && <EyeSlashIcon title='hidden' className='h-4 w-4 text-foreground' />}
                </div>
                <p className={'description-small'}>{bytesToString(media.size)}</p>
            </div>

            {media.mounted ? (
                <Button loading={loading} onClick={handleUnmount} variant={'outline'} color={'danger'} size={'sm'}>
                    Unmount
                </Button>
            ) : (
                <Button loading={loading} onClick={handleMount} variant={'filled'} color={'success'} size={'sm'}>
                    Mount
                </Button>
            )}
        </div>
    )
}

export default MediaRow
