import { useAddresses } from '@/features/servers/detail/api.ts'
import { summarizeAddresses } from '@/features/servers/networking/address-labels.ts'
import AddressRows from '@/features/servers/networking/components/AddressRows.tsx'
import { IconChevronRight } from '@tabler/icons-react'
import { Link, useParams } from '@tanstack/react-router'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton.tsx'

const IpamCard = () => {
    const { data: addresses } = useAddresses()
    const { serverUuid } = useParams({ from: '/_app/servers/$serverUuid' })

    return (
        <Card>
            <CardHeader>
                <CardTitle>IPAM</CardTitle>
                {/* The count line the list used to render above itself, moved
                    into the slot every other card puts a description in: what
                    this card holds, rather than its title again in a sentence. */}
                <CardDescription>
                    {addresses?.length
                        ? summarizeAddresses(addresses)
                        : 'Addresses allocated to this server.'}
                </CardDescription>
                <CardAction>
                    <Button variant={'ghost'} size={'sm'} asChild>
                        <Link
                            to={'/servers/$serverUuid/networking'}
                            params={{ serverUuid }}
                        >
                            Networking
                            {/* The app's "goes somewhere" mark — the same
                                chevron the server rows and account settings
                                trail their links with. Decorative: the button
                                already says where. */}
                            <IconChevronRight
                                aria-hidden
                                className={
                                    'text-muted-foreground size-4 shrink-0'
                                }
                            />
                        </Link>
                    </Button>
                </CardAction>
            </CardHeader>
            {/* Unpadded: the rows are the card's own, so their dividers have to
                reach its edges. Deliberate exception to the Card's `p-4`. */}
            <CardContent className={'p-0'}>
                {addresses ? (
                    <AddressRows addresses={addresses} />
                ) : (
                    /* The same rows, so the card holds still on load. */
                    <ul className={'divide-y border-t'}>
                        {Array.from({ length: 3 }).map((_, index) => (
                            <li key={index} className={'px-4 py-3'}>
                                <Skeleton className={'h-4 w-40'} />
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    )
}

export default IpamCard
