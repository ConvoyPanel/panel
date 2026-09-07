import { IconDots } from '@tabler/icons-react'
import { CellContext, ColumnDef } from '@tanstack/react-table'
import { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

interface Props {
    children: ReactNode
    /**
     * Why this row has no actions, when that is a rule rather than an absence.
     *
     * A menu that opens onto nothing (or onto only the items that happen to be legal) reads as a
     * missing feature; a closed menu that says why reads as the rule it is. Given a reason, the
     * trigger is disabled and carries it as its tooltip and label.
     */
    disabledReason?: string
}

const Actions = ({ children, disabledReason }: Props) => {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    aria-label={disabledReason ?? 'Open menu'}
                    title={disabledReason}
                    disabled={!!disabledReason}
                    variant='ghost'
                    className='data-[state=open]:bg-muted ml-auto flex size-8 p-0'
                >
                    <IconDots className='size-4' aria-hidden='true' />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align='end' className='w-40'>
                {children}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}

export const actionsColumn = <TData,>(
    children: (data: CellContext<TData, any>) => ReactNode,
    /** Returns the reason this row's menu is closed, or undefined when it is open. */
    disabledReason?: (data: CellContext<TData, any>) => string | undefined
): ColumnDef<TData> => {
    return {
        id: 'actions',
        cell: data => {
            return (
                <Actions disabledReason={disabledReason?.(data)}>
                    {children(data)}
                </Actions>
            )
        },
        size: 40,
    }
}

export default Actions
