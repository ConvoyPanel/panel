import FormCard from '@/components/elements/FormCard'
import Button from '@/components/elements/Button'
import useSWR from 'swr'
import { ServerContext } from '@/state/server'
import getMedia from '@/api/server/settings/getMedia'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
import { bytesToString } from '@/util/helpers'
import { useMemo } from 'react'

const MediaContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { data, mutate } = useSWR(['server:settings:hardware:media', uuid], () => getMedia(uuid))

    const mountedCount = useMemo(() => {
        let count = 0

        data?.forEach(iso => {
            if (iso.mounted) {
                count++
            }
        })

        return count
    }, [data])

    const mount = () => {}

    const unmount = () => {}

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>Available Media</FormCard.Title>

                <div className={'flex flex-col space-y-3 mt-3'}>
                    {data?.map(iso => (
                        <div
                            key={iso.uuid}
                            className={'flex items-center justify-between py-2 px-4 border border-accent-200 rounded'}
                        >
                            <div>
                                <div className={'flex items-center space-x-3'}>
                                    <p className='text-foreground'>{iso.name}</p>
                                    {iso.hidden && <EyeSlashIcon title='hidden' className='h-4 w-4 text-foreground' />}
                                </div>
                                <p className={'description-small'}>{bytesToString(iso.size)}</p>
                            </div>

                            {iso.mounted ? (
                                <Button variant={'outline'} color={'danger'} size={'sm'}>
                                    Unmount
                                </Button>
                            ) : (
                                <Button variant={'filled'} color={'success'} size={'sm'}>
                                    Mount
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </FormCard.Body>
        </FormCard>
    )
}

export default MediaContainer
