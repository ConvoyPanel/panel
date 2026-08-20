import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/servers/$serverUuid/activity')({
    staticData: {
        title: 'Activity',
    },
})
