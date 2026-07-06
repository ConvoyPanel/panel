import { Passkey as PasskeyType } from '@/features/account/types.ts'
import { IconKeyFilled, IconPencil, IconTrash } from '@tabler/icons-react'
import { format } from 'date-fns'
import { useMemo } from 'react'
import { useShallow } from 'zustand/react/shallow'

import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'

import { Button } from '@/components/ui/Button'


interface Props {
    passkey: PasskeyType
}

const Passkey = ({ passkey }: Props) => {
    const openModal = usePasskeysModalStore(
        useShallow(state => state.openModal)
    )
    const formattedDate = useMemo(
        () => format(passkey.createdAt, 'MMMM do, yyyy h:mm a'),
        [passkey.createdAt]
    )

    return (
        <div className={'flex items-center py-2 pr-2'}>
            <IconKeyFilled className={'mt-px mr-4'} />
            <div className='space-y-0.5 overflow-x-hidden'>
                <p className='truncate text-sm font-medium leading-none'>
                    {passkey.name}
                </p>
                <p className='text-xs text-muted-foreground'>
                    Added {formattedDate}
                </p>
            </div>
            <div className={'min-w-[1rem] grow'} />
            <div className={'shrink-0'}>
                <Button
                    variant={'ghost'}
                    size={'icon'}
                    onClick={() => openModal('rename', passkey)}
                >
                    <IconPencil className={'h-4 w-4'} />
                </Button>
                <Button
                    variant={'ghost'}
                    size={'icon'}
                    onClick={() => openModal('delete', passkey)}
                >
                    <IconTrash className={'h-4 w-4'} />
                </Button>
            </div>
        </div>
    )
}

export default Passkey
