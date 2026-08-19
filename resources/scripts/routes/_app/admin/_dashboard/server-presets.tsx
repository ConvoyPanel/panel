import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/_dashboard/server-presets')({
    staticData: {
        title: 'Server Presets',
    },
})
