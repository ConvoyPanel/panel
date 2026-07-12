import { usePasskeysModalStore } from '@/features/account/components/PasskeysContainer.tsx'
import { Passkey as PasskeyType } from '@/features/account/types.ts'
import { IconKeyFilled, IconPencil, IconTrash } from '@tabler/icons-react'
import { format } from 'date-fns'
import { useMemo } from 'react'
import { useShallow } from 'zustand/react/shallow'

import { Button } from '@/components/ui/Button'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'

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
        <Item variant={'muted'} size={'sm'}>
            <ItemMedia variant={'icon'}>
                <IconKeyFilled />
            </ItemMedia>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>{passkey.name}</ItemTitle>
                <ItemDescription>Added {formattedDate}</ItemDescription>
            </ItemContent>
            <ItemActions>
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
            </ItemActions>
        </Item>
    )
}

export default Passkey
