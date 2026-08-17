import {
    mediaQueries,
    unmountMedia,
    useMedia,
} from '@/features/servers/media/api.ts'
import { formatBytes } from '@/features/servers/storage/api.ts'
import { getApiErrorMessage } from '@/utils/http.ts'
import { IconDisc } from '@tabler/icons-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

interface Props {
    uuid: string
}

/**
 * What is in the server's optical drive, on the page that lists the drive.
 *
 * The ISO library stays the place to browse and pick an image; this is the
 * other half of that story — the mount itself, next to the boot order that
 * decides whether the server tries it.
 */
const AttachedMediaCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const { data: media, isLoading, isError, refetch } = useMedia(uuid)

    const mounted = media?.find(iso => iso.mounted)

    const { mutate: eject, isPending } = useMutation({
        mutationFn: (isoUuid: string) => unmountMedia(uuid, isoUuid),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: mediaQueries.all(uuid) })
            queryClient.invalidateQueries({
                queryKey: ['servers', uuid, 'storage'],
            })
            toast.add({ title: 'ISO ejected', type: 'success' })
        },
        onError: e =>
            toast.add({
                title: getApiErrorMessage(e, 'Failed to eject the ISO'),
                type: 'error',
            }),
    })

    return (
        <Card>
            <CardHeader>
                <CardTitle>Attached media</CardTitle>
                <CardDescription>
                    The image in this server’s CD/DVD drive.
                </CardDescription>
            </CardHeader>
            <CardContent className={'flex-1'}>
                {isError && !media ? (
                    <CollectionErrorState onRetry={refetch} />
                ) : isLoading ? (
                    <Skeleton className={'h-20 w-full'} />
                ) : mounted ? (
                    <div className={'flex flex-col gap-3'}>
                        <Item variant={'muted'} size={'sm'}>
                            <ItemMedia variant={'icon'}>
                                <IconDisc />
                            </ItemMedia>
                            <ItemContent className={'overflow-x-hidden'}>
                                <ItemTitle className={'truncate'}>
                                    {mounted.name}
                                </ItemTitle>
                                <ItemDescription>
                                    {formatBytes(mounted.size)}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Button
                                    variant={'outline'}
                                    loading={isPending}
                                    onClick={() => eject(mounted.uuid)}
                                >
                                    Eject
                                </Button>
                            </ItemActions>
                        </Item>
                        <p className={'text-muted-foreground text-xs'}>
                            Switch the drive on above to boot from it — an
                            attached image the boot order skips is never read.
                        </p>
                    </div>
                ) : (
                    <SimpleEmptyState
                        icon={IconDisc}
                        title={'Drive is empty'}
                        description={
                            'Mount an image to install an operating system or boot a rescue environment.'
                        }
                        action={
                            <Button variant={'outline'} asChild>
                                <Link
                                    to={'/servers/$serverUuid/iso-library'}
                                    params={{ serverUuid: uuid }}
                                >
                                    Browse ISO library
                                </Link>
                            </Button>
                        }
                    />
                )}
            </CardContent>
        </Card>
    )
}

export default AttachedMediaCard
