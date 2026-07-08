import { SSHKey as Key } from '@/features/account/types.ts'
import { IconKeyFilled, IconTrash } from '@tabler/icons-react'
import { format } from 'date-fns'
import { useMemo } from 'react'

import { sshKeyAlgorithm } from '@/features/account/ssh-keys/api.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'

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
        <div className={'flex items-center py-2 pr-2'}>
            <IconKeyFilled className={'mt-px mr-4 shrink-0'} />
            <div className='space-y-1 overflow-x-hidden'>
                <p className='truncate text-sm font-medium leading-none'>
                    {key.name}
                </p>
                <div className={'flex items-center gap-2'}>
                    <Badge variant={'secondary'} className={'font-mono'}>
                        {sshKeyAlgorithm(key.publicKey)}
                    </Badge>
                    <span className={'text-xs text-muted-foreground'}>
                        Added {formattedDate}
                    </span>
                </div>
            </div>
            <div className={'min-w-[1rem] grow'} />
            <Button
                variant={'ghost'}
                size={'icon'}
                className={'shrink-0'}
                onClick={() => onDelete(key)}
            >
                <IconTrash className={'h-4 w-4'} />
            </Button>
        </div>
    )
}

export default SSHKey
