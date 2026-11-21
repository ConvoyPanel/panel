import {
    type ApiKey as ApiKeyType,
    apiKeyScopes,
} from '@/features/account/api-keys/api.ts'
import {
    describeTokenExpiry,
    isTokenExpired,
} from '@/features/tokens/expiry.ts'
import { cn } from '@/utils'
import { IconTrash } from '@tabler/icons-react'
import { formatDistanceToNow } from 'date-fns'
import { useMemo } from 'react'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Item, ItemActions, ItemContent, ItemTitle } from '@/components/ui/Item'

/** A human label for a token's granted scope. */
const scopeLabel = (abilities: string[]): string =>
    apiKeyScopes.find(scope => scope.value === abilities[0])?.label ??
    abilities.join(', ')

interface Props {
    apiKey: ApiKeyType
    onDelete: (apiKey: ApiKeyType) => void
}

const ApiKey = ({ apiKey, onDelete }: Props) => {
    const lastUsed = useMemo(
        () =>
            apiKey.lastUsedAt
                ? `Last used ${formatDistanceToNow(apiKey.lastUsedAt, { addSuffix: true })}`
                : 'Never used',
        [apiKey.lastUsedAt]
    )

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>{apiKey.name}</ItemTitle>
                <div className={'flex items-center gap-2'}>
                    <Badge variant={'secondary'}>
                        {scopeLabel(apiKey.abilities)}
                    </Badge>
                    <span className={'text-muted-foreground text-xs'}>
                        {lastUsed}
                    </span>
                    <span
                        className={cn(
                            'text-xs',
                            isTokenExpired(apiKey.expiresAt)
                                ? 'text-destructive'
                                : 'text-muted-foreground'
                        )}
                    >
                        {describeTokenExpiry(apiKey.expiresAt)}
                    </span>
                </div>
            </ItemContent>
            <ItemActions>
                <Button
                    variant={'ghost'}
                    size={'icon'}
                    aria-label={'Delete API key'}
                    onClick={() => onDelete(apiKey)}
                >
                    <IconTrash className={'h-4 w-4'} />
                </Button>
            </ItemActions>
        </Item>
    )
}

export default ApiKey
