import { IconTrash } from '@tabler/icons-react'
import { formatDistanceToNow } from 'date-fns'
import { useMemo } from 'react'

import {
    apiKeyScopes,
    type ApiKey as ApiKeyType,
} from '@/features/account/api-keys/api.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemTitle,
} from '@/components/ui/Item'

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
        <Item variant={'muted'}>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>{apiKey.name}</ItemTitle>
                <div className={'flex items-center gap-2'}>
                    <Badge variant={'secondary'}>
                        {scopeLabel(apiKey.abilities)}
                    </Badge>
                    <span className={'text-xs text-muted-foreground'}>
                        {lastUsed}
                    </span>
                </div>
            </ItemContent>
            <ItemActions>
                <Button
                    variant={'ghost'}
                    size={'icon'}
                    onClick={() => onDelete(apiKey)}
                >
                    <IconTrash className={'h-4 w-4'} />
                </Button>
            </ItemActions>
        </Item>
    )
}

export default ApiKey
