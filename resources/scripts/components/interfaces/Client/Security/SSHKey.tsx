import { SSHKey as Key } from '@/types/keychain.ts'
import { IconKeyFilled } from '@tabler/icons-react'
import { format } from 'date-fns'
import { useMemo } from 'react'

interface Props {
    publicKey: Key
}

const SSHKey = ({ publicKey: key }: Props) => {
    const formattedDate = useMemo(
        () => format(key.createdAt, 'MMMM do, yyyy'),
        [key.createdAt]
    )

    return (
        <div
            className={
                '-mx-2 flex items-start space-x-4 rounded-md p-2 transition-all hover:bg-accent hover:text-accent-foreground'
            }
        >
            <IconKeyFilled className={'mt-px'} />
            <div className='space-y-0.5'>
                <p className='text-sm font-medium leading-none'>{key.name}</p>
                <p className='text-xs text-muted-foreground'>
                    Added {formattedDate}
                </p>
            </div>
        </div>
    )
}

export default SSHKey
