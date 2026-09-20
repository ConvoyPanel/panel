import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/_dashboard/adoptable-guests')(
    {
        staticData: { title: 'Unmanaged Guests' },
    }
)
