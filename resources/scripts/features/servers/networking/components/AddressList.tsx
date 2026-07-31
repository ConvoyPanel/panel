import useClipboard from '@/hooks/use-clipboard.ts'
import { Address, AddressVersion } from '@/types/address.ts'
import { cn } from '@/utils'
import { IconWifiOff } from '@tabler/icons-react'
import { KeyboardEvent, ReactNode } from 'react'

import { Badge } from '@/components/ui/Badge'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { Item, OverflowItemGroup } from '@/components/ui/Item'

const versionLabels: Record<AddressVersion, string> = {
    [AddressVersion.IPv4]: 'IPv4',
    [AddressVersion.IPv6]: 'IPv6',
}

const CopyValue = ({
    label,
    value,
    className,
    children,
}: {
    label: string
    value: string
    className?: string
    children?: ReactNode
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
                'hover:text-primary focus-visible:ring-ring/50 cursor-pointer rounded-sm font-mono outline-none select-none focus-visible:ring-[3px]',
                className
            )}
        >
            {children ?? value}
        </span>
    )
}

const Field = ({ label, value }: { label: string; value: string | null }) => (
    <div className={'flex min-w-0 flex-col gap-1'}>
        <span className={'text-label text-xs font-medium'}>{label}</span>
        {value ? (
            <CopyValue
                label={label}
                value={value}
                className={'block truncate text-sm font-medium'}
            />
        ) : (
            <span
                className={'text-muted-foreground truncate font-mono text-sm'}
            >
                N/A
            </span>
        )}
    </div>
)

const AddressRow = ({ address }: { address: Address }) => (
    <Item variant={'muted'} className={'flex-col items-stretch gap-3 p-4'}>
        <div className={'flex items-start justify-between gap-3'}>
            <div
                className={
                    'flex min-w-0 items-baseline font-mono tracking-tight tabular-nums'
                }
            >
                <CopyValue
                    label={'Address'}
                    value={`${address.ip}/${address.prefixLength}`}
                    /* Semibold gives the address its scannability; on the dark
                       card near-pure-white bloom reads as heavy, so soften the
                       IP's white there while keeping full-contrast bold on light. */
                    className={
                        'text-foreground dark:hover:text-primary text-[21px] leading-tight font-semibold break-all dark:text-[oklch(0.9_0.004_106.5)]'
                    }
                >
                    {address.ip}
                </CopyValue>
                <span
                    className={
                        'text-muted-foreground text-[21px] leading-tight font-medium'
                    }
                >
                    /{address.prefixLength}
                </span>
            </div>
            <Badge variant={'secondary'} className={'shrink-0'}>
                {versionLabels[address.version]}
            </Badge>
        </div>
        <div className={'grid grid-cols-2 gap-x-4 gap-y-2 border-t pt-3'}>
            <Field label={'Gateway'} value={address.gateway} />
            <Field label={'MAC address'} value={address.macAddress} />
        </div>
    </Item>
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
                <AddressRow key={address.id} address={address} />
            ))}
        />
    )
}

export default AddressList
