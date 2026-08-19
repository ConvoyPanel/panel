import StorageDetail from '@/features/nodes/components/Storages/StorageDetail.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/storage/$storageId'
)({
    component: StorageDetailPage,
})

function StorageDetailPage() {
    const { storageId } = Route.useParams()

    return <StorageDetail storageId={Number(storageId)} />
}
