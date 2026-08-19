import { Outlet, createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/servers')({
    component: Outlet,
})
