import { Address } from '@/types/address.ts'
import { IconWifi } from '@tabler/icons-react'
import { KeyboardEvent } from 'react'

import useAddressesSWR from '@/api/servers/use-addresses-swr.ts'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'


const Cell = ({
    title,
    description,
    className,
}: {
    title: string
    description: string
    className?: string
}) => {
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(description)
            toast({ description: 'Copied to clipboard' })
        } catch {
            toast({
                description: 'Failed to copy to clipboard',
                variant: 'destructive',
            })
        }
    }

    const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Enter' || event.key === ' ') {
            copy()
        }
    }

    return (
        <div className={className}>
            <dd
                role='button'
                tabIndex={0}
                className={'cursor-pointer select-none truncate text-sm'}
                onClick={copy}
                onKeyDown={handleKeyDown}
            >
                {description}
            </dd>
            <dt className={'text-xs text-muted-foreground'}>{title}</dt>
        </div>
    )
}

const RenderAddresses = ({ addresses }: { addresses: Address[] }) => {
    if (addresses.length === 0) {
        // make a description

        return (
            <SimpleEmptyState
                icon={IconWifi}
                title={'No IP Addresses'}
                description={
                    'Your server does not have any IP addresses assigned to it. Network connectivity may not be available.'
                }
            />
        )
    }

    if (addresses.length > 0) {
        return (
            <ul className={'divide-y rounded-md border'}>
                {addresses.map(address => (
                    <li key={address.id} className={'py-2 px-3'}>
                        <dl
                            className={
                                'relative flex flex-col gap-2 @sm:flex-row'
                            }
                        >
                            <Cell
                                title={'Address'}
                                description={address.address}
                                className={'w-full @sm:max-w-[30%]'}
                            />
                            <Cell
                                title={'Gateway'}
                                description={address.gateway}
                                className={'w-full @sm:max-w-[30%]'}
                            />
                            <Cell
                                title={'Mac Address'}
                                description={address.macAddress ?? 'N/A'}
                            />
                        </dl>
                    </li>
                ))}
            </ul>
        )
    }
}

const IpamCard = () => {
    const { data: addresses } = useAddressesSWR()

    return (
        <Card className={'col-span-2 min-h-[15rem] @md:col-span-4'}>
            <CardHeader>
                <CardTitle>IPAM</CardTitle>
            </CardHeader>
            <CardContent>
                {addresses ? (
                    <RenderAddresses addresses={addresses} />
                ) : (
                    <Skeleton className={'h-8 w-full'} />
                )}
            </CardContent>
        </Card>
    )
}

export default IpamCard
