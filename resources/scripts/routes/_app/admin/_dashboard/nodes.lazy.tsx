import { Outlet, createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes')({
    component: Outlet,
})
