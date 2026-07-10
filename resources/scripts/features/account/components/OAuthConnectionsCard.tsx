import { useMutation } from '@tanstack/react-query'
import {
    IconBrandGithub,
    IconBrandGitlab,
    IconBrandGoogle,
    IconPlugConnected,
    type Icon,
} from '@tabler/icons-react'
import { toast } from 'sonner'

import {
    oauthConnectionQueries,
    unlinkOAuthConnection,
    useOAuthConnections,
    type OAuthConnection,
} from '@/features/account/oauth/api.ts'
import { oauthProviders, oauthRedirectUrl } from '@/features/auth/oauth.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

const ICONS: Record<string, Icon> = {
    google: IconBrandGoogle,
    github: IconBrandGithub,
    gitlab: IconBrandGitlab,
}

const OAuthConnectionsCard = () => {
    const providers = oauthProviders()
    const confirm = useConfirmationStore(state => state.confirm)
    const mutate = useQueryMutator<OAuthConnection[]>(
        oauthConnectionQueries.all()
    )

    const { data: connections, isLoading } = useOAuthConnections()

    const { mutate: unlink } = useMutation({
        mutationFn: (connection: OAuthConnection) =>
            unlinkOAuthConnection(connection.id),
        onSuccess: (_, connection) => {
            mutate(list => list?.filter(c => c.id !== connection.id))
            toast.success(`Disconnected ${connection.label}`)
        },
        onError: () => toast.error('Failed to disconnect provider'),
    })

    // No providers configured on this install — don't render an empty card.
    if (providers.length === 0) {
        return null
    }

    const connectionFor = (providerId: string): OAuthConnection | undefined =>
        connections?.find(c => c.provider === providerId)

    const handleDisconnect = async (connection: OAuthConnection) => {
        const confirmed = await confirm({
            title: `Disconnect ${connection.label}`,
            description: `You will no longer be able to sign in with ${connection.label}. Make sure you can still access your account another way.`,
        })
        if (!confirmed) return

        unlink(connection)
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Connected Accounts</CardTitle>
                <CardDescription>
                    Sign in faster by connecting an external identity provider.
                </CardDescription>
            </CardHeader>
            <CardContent
                className={cn('min-h-[8rem]', isLoading && 'grid place-items-center')}
            >
                {isLoading || !connections ? (
                    <Skeleton className={'h-24 w-full'} />
                ) : (
                    <ItemGroup className={'gap-3'}>
                        {providers.map(provider => {
                            const ProviderIcon =
                                ICONS[provider.id] ?? IconPlugConnected
                            const connection = connectionFor(provider.id)

                            return (
                                <Item
                                    key={provider.id}
                                    variant={'muted'}
                                >
                                    <ItemMedia variant={'icon'}>
                                        <ProviderIcon
                                            className={'text-muted-foreground'}
                                        />
                                    </ItemMedia>
                                    <ItemContent>
                                        <ItemTitle>{provider.label}</ItemTitle>
                                        {connection?.email && (
                                            <ItemDescription>
                                                {connection.email}
                                            </ItemDescription>
                                        )}
                                    </ItemContent>
                                    <ItemActions>
                                        {connection ? (
                                            <Button
                                                variant={'outline'}
                                                size={'sm'}
                                                onClick={() =>
                                                    handleDisconnect(connection)
                                                }
                                            >
                                                Disconnect
                                            </Button>
                                        ) : (
                                            <Button
                                                variant={'outline'}
                                                size={'sm'}
                                                onClick={() => {
                                                    window.location.href =
                                                        oauthRedirectUrl(
                                                            provider.id
                                                        )
                                                }}
                                            >
                                                Connect
                                            </Button>
                                        )}
                                    </ItemActions>
                                </Item>
                            )
                        })}
                    </ItemGroup>
                )}
            </CardContent>
        </Card>
    )
}

export default OAuthConnectionsCard
