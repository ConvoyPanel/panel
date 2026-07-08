import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/servers/$serverUuid/security')({
    staticData: {
        title: 'Security',
    },
})
