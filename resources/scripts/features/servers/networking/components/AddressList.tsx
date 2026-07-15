import useClipboard from '@/hooks/use-clipboard.ts'
import { Address } from '@/types/address.ts'
import { cn } from '@/utils'
import { IconNetwork, IconWifiOff } from '@tabler/icons-react'
import { KeyboardEvent } from 'react'

import { Badge } from '@/components/ui/Badge'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemContent,
    ItemMedia,
    ItemTitle,
    OverflowItemGroup,
} from '@/components/ui/Item'

const CopyValue = ({
    label,
    value,
    className,
}: {
    label: string
    value: string
    className?: string
}) => {
    const { copy } = useClipboard({ successMessage: 'Copied to clipboard' })

    const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault()
            copy(value)
        }
    }

    return (
        <span
            role={'button'}
            tabIndex={0}
            aria-label={`Click to copy ${label} ${value}`}
            onClick={() => copy(value)}
            onKeyDown={handleKeyDown}
            className={cn(
                'hover:text-primary focus-visible:ring-ring/50 cursor-pointer truncate rounded-sm font-mono outline-none select-none focus-visible:ring-[3px]',
                className
            )}
        >
            {value}
        </span>
    )
}

const Field = ({ label, value }: { label: string; value: string }) => (
    <div className={'min-w-0'}>
        <CopyValue label={label} value={value} className={'block text-xs'} />
        <p className={'text-xs text-muted-foreground'}>{label}</p>
    </div>
)

interface Props {
    addresses: Address[]
}

const AddressList = ({ addresses }: Props) => {
    if (addresses.length === 0) {
        return (
            <SimpleEmptyState
                icon={IconWifiOff}
                title={'No IP addresses'}
                description={
                    'This server does not have any IP addresses assigned to it.'
                }
            />
        )
    }

    return (
        <OverflowItemGroup
            max={4}
            title={'IP Addresses'}
            rows={addresses.map(address => (
                <Item key={address.id} variant={'muted'} size={'sm'}>
                    <ItemMedia variant={'icon'}>
                        <IconNetwork />
                    </ItemMedia>
                    <ItemContent className={'min-w-0 overflow-x-hidden'}>
                        <ItemTitle className={'max-w-full'}>
                            <CopyValue
                                label={'Address'}
                                value={`${address.ip}/${address.prefixLength}`}
                                className={'truncate text-sm'}
                            />
                            <Badge variant={'secondary'} className={'shrink-0'}>
                                {address.version}
                            </Badge>
                        </ItemTitle>
                        <div className={'flex flex-wrap gap-x-6 gap-y-1'}>
                            <Field label={'Gateway'} value={address.gateway} />
                            <Field
                                label={'MAC'}
                                value={address.macAddress ?? 'N/A'}
                            />
                        </div>
                    </ItemContent>
                </Item>
            ))}
        />
    )
}

export default AddressList
