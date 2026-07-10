import { SSHKey as Key } from '@/features/account/types.ts'
import { IconKeyFilled, IconTrash } from '@tabler/icons-react'
import { format } from 'date-fns'
import { useMemo } from 'react'

import { sshKeyAlgorithm } from '@/features/account/ssh-keys/api.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'

interface Props {
    publicKey: Key
    onDelete: (key: Key) => void
}

const SSHKey = ({ publicKey: key, onDelete }: Props) => {
    const formattedDate = useMemo(
        () => format(key.createdAt, 'MMMM do, yyyy'),
        [key.createdAt]
    )

    return (
        <Item variant={'muted'}>
            <ItemMedia variant={'icon'}>
                <IconKeyFilled />
            </ItemMedia>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>{key.name}</ItemTitle>
                <div className={'flex items-center gap-2'}>
                    <Badge variant={'secondary'} className={'font-mono'}>
                        {sshKeyAlgorithm(key.publicKey)}
                    </Badge>
                    <span className={'text-xs text-muted-foreground'}>
                        Added {formattedDate}
                    </span>
                </div>
            </ItemContent>
            <ItemActions>
                <Button
                    variant={'ghost'}
                    size={'icon'}
                    onClick={() => onDelete(key)}
                >
                    <IconTrash className={'h-4 w-4'} />
                </Button>
            </ItemActions>
        </Item>
    )
}

export default SSHKey
