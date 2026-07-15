import { createLazyFileRoute } from '@tanstack/react-router'

import BackupQuotaSidebar from '@/features/servers/components/client/Backups/BackupQuotaSidebar.tsx'
import BackupView from '@/features/servers/components/client/Backups/BackupView.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'


export const Route = createLazyFileRoute('/_app/servers/$serverUuid/backups')({
    component: ServerBackups,
    // @ts-ignore
    meta: () => [{ title: 'Backups' }],
})

function ServerBackups() {
    return (
        <>
            <div className={'flex flex-wrap items-center justify-between gap-2'}>
                <Heading>Backups</Heading>
                <BackupQuotaSidebar />
            </div>
            <BackupView />
        </>
    )
}
