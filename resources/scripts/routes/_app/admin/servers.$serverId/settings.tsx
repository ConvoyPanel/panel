import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/servers/$serverId/settings')({
    staticData: {
        title: 'Build & limits',
    },
})
