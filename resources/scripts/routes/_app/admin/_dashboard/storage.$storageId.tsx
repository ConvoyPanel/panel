import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/_dashboard/storage/$storageId')({
    staticData: {
        title: 'Storage',
    },
})
