import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/account/')({
    staticData: {
        title: 'Profile',
    },
})
