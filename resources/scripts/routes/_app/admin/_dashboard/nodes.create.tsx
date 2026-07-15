import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/_dashboard/nodes/create')({
    staticData: {
        title: 'New node',
    },
})
