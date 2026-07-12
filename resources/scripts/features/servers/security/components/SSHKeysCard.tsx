import { useMutation, useQueryClient } from '@tanstack/react-query'
import { IconClipboard, IconKey, IconPlus, IconTrash } from '@tabler/icons-react'
import { AxiosError } from 'axios'
import { useEffect, useMemo, useState } from 'react'
import { toast } from 'sonner'

import { useSSHKeys } from '@/features/account/ssh-keys/api.ts'
import {
    describeSSHKey,
    serverSSHKeyQueries,
    updateServerSSHKeys,
    useServerSSHKeys,
} from '@/features/servers/security/api.ts'

import PasteKeyDialog from '@/features/servers/security/components/PasteKeyDialog.tsx'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemMedia,
    ItemTitle,
    OverflowItemGroup,
} from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

const errorMessage = (e: unknown, fallback: string): string =>
    e instanceof AxiosError && e.response?.data?.message
        ? e.response.data.message
        : fallback

const sameSet = (a: string[], b: string[]) =>
    a.length === b.length &&
    [...a].sort().join('\n') === [...b].sort().join('\n')

interface Props {
    uuid: string
}

const SSHKeysCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const { data: serverKeys, isLoading } = useServerSSHKeys(uuid)
    const { data: keychain } = useSSHKeys()

    const [keys, setKeys] = useState<string[]>([])
    const [pasteOpen, setPasteOpen] = useState(false)

    // Seed the working copy from the server, and re-seed whenever the saved set changes (e.g. after
    // a successful save invalidates the query).
    useEffect(() => {
        if (serverKeys) setKeys(serverKeys)
    }, [serverKeys])

    const dirty = useMemo(
        () => (serverKeys ? !sameSet(keys, serverKeys) : false),
        [keys, serverKeys]
    )

    // Keychain keys not already on the server (compared by the normalized public-key text).
    const available = useMemo(() => {
        const present = new Set(keys.map(k => k.trim()))
        return (keychain ?? []).filter(k => !present.has(k.publicKey.trim()))
    }, [keychain, keys])

    const addKeys = (incoming: string[]) =>
        setKeys(prev => {
            const present = new Set(prev.map(k => k.trim()))
            const additions = incoming.filter(k => !present.has(k.trim()))
            if (additions.length === 0) {
                toast.info('That key is already on this server')
                return prev
            }
            return [...prev, ...additions]
        })

    const { mutate: save, isPending } = useMutation({
        mutationFn: () => updateServerSSHKeys(uuid, keys),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: serverSSHKeyQueries.all(uuid),
            })
            toast.success('SSH keys updated')
        },
        onError: e => toast.error(errorMessage(e, 'Failed to update keys')),
    })

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>SSH Keys</CardTitle>
                    <CardDescription>
                        Public keys allowed to sign in to this server. Add from
                        your keychain, or paste a one-off key.
                    </CardDescription>
                </CardHeader>
                <CardContent className={'min-h-[10rem]'}>
                    {isLoading ? (
                        <Skeleton className={'h-32 w-full'} />
                    ) : keys.length > 0 ? (
                        <OverflowItemGroup
                            max={4}
                            title={'SSH Keys'}
                            rows={keys.map(key => {
                                const { algorithm, comment } =
                                    describeSSHKey(key)
                                const named = (keychain ?? []).find(
                                    k => k.publicKey.trim() === key.trim()
                                )
                                return (
                                    <Item
                                        key={key}
                                        variant={'muted'}
                                        size={'sm'}
                                    >
                                        <ItemMedia variant={'icon'}>
                                            <IconKey />
                                        </ItemMedia>
                                        <ItemContent
                                            className={'overflow-x-hidden'}
                                        >
                                            <ItemTitle className={'truncate'}>
                                                {named?.name ??
                                                    comment ??
                                                    'One-off key'}
                                                {!named && (
                                                    <Badge variant={'outline'}>
                                                        One-off
                                                    </Badge>
                                                )}
                                            </ItemTitle>
                                            <Badge
                                                variant={'secondary'}
                                                className={'w-fit font-mono'}
                                            >
                                                {algorithm}
                                            </Badge>
                                        </ItemContent>
                                        <ItemActions>
                                            <Button
                                                variant={'ghost'}
                                                size={'icon'}
                                                onClick={() =>
                                                    setKeys(prev =>
                                                        prev.filter(
                                                            k => k !== key
                                                        )
                                                    )
                                                }
                                            >
                                                <IconTrash
                                                    className={'h-4 w-4'}
                                                />
                                            </Button>
                                        </ItemActions>
                                    </Item>
                                )
                            })}
                        />
                    ) : (
                        <SimpleEmptyState
                            icon={IconKey}
                            title={'No SSH keys'}
                            description={
                                'This server has no authorized SSH keys.'
                            }
                        />
                    )}

                    <div className={'mt-4 flex flex-wrap gap-2'}>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant={'outline'}
                                    size={'sm'}
                                    disabled={available.length === 0}
                                >
                                    <IconPlus className={'mr-2 size-4'} />
                                    Add from keychain
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align={'start'}>
                                <DropdownMenuLabel>
                                    Your keychain
                                </DropdownMenuLabel>
                                {available.map(k => (
                                    <DropdownMenuItem
                                        key={k.id}
                                        onClick={() => addKeys([k.publicKey])}
                                    >
                                        {k.name}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            variant={'outline'}
                            size={'sm'}
                            onClick={() => setPasteOpen(true)}
                        >
                            <IconClipboard className={'mr-2 size-4'} />
                            Paste a key
                        </Button>
                    </div>
                </CardContent>
                {dirty && (
                    <CardFooter className={'flex justify-end gap-3'}>
                        <Button
                            variant={'outline'}
                            onClick={() => serverKeys && setKeys(serverKeys)}
                            disabled={isPending}
                        >
                            Reset
                        </Button>
                        <Button onClick={() => save()} loading={isPending}>
                            Save changes
                        </Button>
                    </CardFooter>
                )}
            </Card>

            <PasteKeyDialog
                open={pasteOpen}
                onOpenChange={setPasteOpen}
                onAdd={addKeys}
            />
        </>
    )
}

export default SSHKeysCard
