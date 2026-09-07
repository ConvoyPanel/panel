import { IconTrash } from '@tabler/icons-react'
import { ReactNode } from 'react'

import { TablerIcon } from '@/lib/tabler.ts'

import { Button } from '@/components/ui/Button'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'

interface Props {
    icon: TablerIcon
    name: string
    /** A badge or two, then whatever dates the credential carries. */
    meta: ReactNode
    revokeLabel: string
    onRevoke: () => void
}

/** One credential, rendered the way the account's own security page renders its keys. */
const CredentialRow = ({ icon: Icon, name, meta, revokeLabel, onRevoke }: Props) => (
    <Item variant={'muted'} size={'sm'}>
        <ItemMedia variant={'icon'}>
            <Icon />
        </ItemMedia>
        <ItemContent className={'overflow-x-hidden'}>
            <ItemTitle className={'truncate'}>{name}</ItemTitle>
            <div className={'flex flex-wrap items-center gap-x-2 gap-y-1'}>
                {meta}
            </div>
        </ItemContent>
        <ItemActions>
            <Button
                variant={'ghost'}
                size={'icon'}
                aria-label={revokeLabel}
                onClick={onRevoke}
            >
                <IconTrash className={'h-4 w-4'} />
            </Button>
        </ItemActions>
    </Item>
)

export default CredentialRow
