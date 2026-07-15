import { createLazyFileRoute } from '@tanstack/react-router'

import { useServer } from '@/features/servers/detail/api.ts'

import OSSelectionForm from '@/features/servers/components/client/Rebuild/OSSelectionForm'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import Skeleton from '@/components/ui/Skeleton'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/rebuild')({
    component: RebuildServerPage,
    // @ts-ignore
    meta: () => [{ title: 'Rebuild' }],
})

// Mirrors OSSelectionForm: two rich h-auto selectors (OS family, template), a
// standard h-8 password input, then a full-width submit.
const skeletonFields = [
    { label: 'w-24', control: 'h-10' },
    { label: 'w-24', control: 'h-10' },
    { label: 'w-32', control: 'h-8' },
]

function RebuildServerPage() {
    const { serverUuid } = Route.useParams()
    const { data: server } = useServer(serverUuid)

    return (
        <>
            <Heading>Rebuild Server</Heading>
            {/* The same one-column-of-two grid the sibling subpages use, so the
                card lands at the established card width rather than a bespoke
                max-w. AppLayout owns the vertical rhythm (gap-2/@md:gap-4), so
                there is no wrapper of our own. */}
            <div className={'grid grid-cols-1 gap-2 @md:grid-cols-2 @md:gap-4'}>
                <Card>
                    <CardHeader>
                        <CardTitle>Operating System Selection</CardTitle>
                        <CardDescription>
                            Choose an operating system to reinstall on your
                            server. All data on the current disk will be lost.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {!server ? (
                            <div className={'space-y-4'}>
                                {skeletonFields.map((field, index) => (
                                    <div key={index} className={'space-y-2'}>
                                        <Skeleton
                                            className={`h-4 ${field.label}`}
                                        />
                                        <Skeleton
                                            className={`${field.control} w-full`}
                                        />
                                    </div>
                                ))}
                                <Skeleton className={'h-8 w-full'} />
                            </div>
                        ) : (
                            <OSSelectionForm server={server} />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    )
}
