import { createLazyFileRoute } from '@tanstack/react-router'

import BackupQuotaSidebar from '@/components/interfaces/Client/Server/Backups/BackupQuotaSidebar.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'


export const Route = createLazyFileRoute('/_app/servers/$serverUuid/backups')({
    component: ServerBackups,
    // @ts-ignore
    meta: () => [{ title: 'Backups' }],
})

function ServerBackups() {
    return (
        <>
            <Heading>Backups</Heading>
            <BackupQuotaSidebar />
        </>
    )
}
