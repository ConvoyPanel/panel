import { createFileRoute } from '@tanstack/react-router'

/*
 * No `staticData.title`: Breadcrumbs renders one crumb per match that has one,
 * and the parent already contributes "Storage" -- declaring it again here is
 * what produced "Storage > Storage". The storage's own name is on the page
 * heading, where it can be the real one rather than a static string.
 */
export const Route = createFileRoute(
    '/_app/admin/_dashboard/storage/$storageId'
)({})
