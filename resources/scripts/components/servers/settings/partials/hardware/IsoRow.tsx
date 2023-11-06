import { ServerContext } from '@/state/server'
import { bytesToString } from '@/util/helpers'
import { useFlashKey } from '@/util/useFlash'
import { EyeSlashIcon } from '@heroicons/react/20/solid'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import { Iso } from '@/api/server/settings/getIsos'
import mountMedia from '@/api/server/settings/mountMedia'
import unmountMedia from '@/api/server/settings/unmountMedia'
import useBootOrderSWR from '@/api/server/settings/useBootOrderSWR'
import useIsosSWR from '@/api/server/settings/useIsosSWR'

import Button from '@/components/elements/Button'


interface Props {
    iso: Iso
}

const IsoRow = ({ iso }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `servers.${uuid}.settings.hardware.isos`
    )
    const { mutate: mutateMedia } = useIsosSWR(uuid)
    const { mutate: mutateBootOrder } = useBootOrderSWR(uuid)
    const [loading, setLoading] = useState(false)

    const handleMount = async () => {
        setLoading(true)
        clearFlashes()
        try {
            await mountMedia(uuid, iso.uuid)

            mutateMedia(data => {
                return data?.map(iso => {
                    if (iso.uuid === iso.uuid) {
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
            await unmountMedia(uuid, iso.uuid)

            mutateMedia(data => {
                return data?.map(iso => {
                    if (iso.uuid === iso.uuid) {
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
        <div
            className={
                'flex items-center justify-between py-2 px-4 border border-accent-200 rounded'
            }
        >
            <div>
                <div className={'flex items-center space-x-3'}>
                    <p className='text-foreground'>{iso.name}</p>
                    {iso.hidden && (
                        <EyeSlashIcon
                            title='hidden'
                            className='h-4 w-4 text-foreground'
                        />
                    )}
                </div>
                <p className={'description-small'}>{bytesToString(iso.size)}</p>
            </div>

            {iso.mounted ? (
                <Button
                    loading={loading}
                    onClick={handleUnmount}
                    variant={'outline'}
                    color={'danger'}
                    size={'sm'}
                >
                    {tStrings('unmount')}
                </Button>
            ) : (
                <Button
                    loading={loading}
                    onClick={handleMount}
                    variant={'filled'}
                    color={'success'}
                    size={'sm'}
                >
                    {tStrings('mount')}
                </Button>
            )}
        </div>
    )
}

export default IsoRow