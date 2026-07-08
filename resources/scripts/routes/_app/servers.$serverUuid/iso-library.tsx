import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/servers/$serverUuid/iso-library')({
    staticData: {
        title: 'ISO Library',
    },
})
