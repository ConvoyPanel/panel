import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/_dashboard/ipam')({
    beforeLoad: () => {
        return {
            getTitle: () => 'IPAM',
        }
    },
    staticData: {
        title: 'IPAM',
    }
})