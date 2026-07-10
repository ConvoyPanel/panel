import { useMutation, useQueryClient } from '@tanstack/react-query'
import { IconDisc } from '@tabler/icons-react'
import byteSize from 'byte-size'
import { toast } from 'sonner'

import {
    mediaQueries,
    mountMedia,
    unmountMedia,
    useMedia,
    type ServerMedia,
} from '@/features/servers/media/api.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
    OverflowItemGroup,
} from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    uuid: string
}

const IsoLibraryCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const { data: media, isLoading } = useMedia(uuid)

    const { mutate: toggle, isPending } = useMutation({
        mutationFn: (iso: ServerMedia) =>
            iso.mounted
                ? unmountMedia(uuid, iso.uuid)
                : mountMedia(uuid, iso.uuid),
        onSuccess: (_, iso) => {
            queryClient.invalidateQueries({ queryKey: mediaQueries.all(uuid) })
            toast.success(iso.mounted ? 'ISO unmounted' : 'ISO mounted')
        },
        onError: () => toast.error('Failed to change mounted media'),
    })

    return (
        <Card>
            <CardHeader>
                <CardTitle>ISO Library</CardTitle>
                <CardDescription>
                    Mount installation media to this server’s CD/DVD drive.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {isLoading || !media ? (
                    <Skeleton className={'h-32 w-full'} />
                ) : media.length > 0 ? (
                    <OverflowItemGroup
                        max={4}
                        title={'ISO Library'}
                        rows={media.map(iso => {
                            const size = byteSize(iso.size, {
                                units: 'iec',
                                precision: 1,
                            })
                            return (
                                <Item
                                    key={iso.uuid}
                                    variant={'muted'}
                                    size={'sm'}
                                >
                                    <ItemMedia variant={'icon'}>
                                        <IconDisc />
                                    </ItemMedia>
                                    <ItemContent className={'overflow-x-hidden'}>
                                        <ItemTitle className={'truncate'}>
                                            {iso.name}
                                            {iso.mounted && (
                                                <Badge variant={'secondary'}>
                                                    Mounted
                                                </Badge>
                                            )}
                                        </ItemTitle>
                                        <ItemDescription>
                                            {size.value} {size.unit}
                                        </ItemDescription>
                                    </ItemContent>
                                    <ItemActions>
                                        <Button
                                            variant={
                                                iso.mounted
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                            size={'sm'}
                                            disabled={isPending}
                                            onClick={() => toggle(iso)}
                                        >
                                            {iso.mounted ? 'Unmount' : 'Mount'}
                                        </Button>
                                    </ItemActions>
                                </Item>
                            )
                        })}
                    />
                ) : (
                    <SimpleEmptyState
                        icon={IconDisc}
                        title={'No media available'}
                        description={
                            'This server’s node has no ISO images available.'
                        }
                    />
                )}
            </CardContent>
        </Card>
    )
}

export default IsoLibraryCard
