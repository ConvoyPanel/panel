import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import useAddressBlockGroupSWR, {
    preloadAddressBlockGroup,
} from '@/api/admin/addressBlockGroups/use-address-block-group-swr.ts'

export const Route = createFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId'
)({
    loader: ({ params: { addressBlockGroupId } }) =>
        preloadAddressBlockGroup(Number(addressBlockGroupId)).catch(
            processAxiosError
        ),
    component: BlockGroupsLayout,
})

function BlockGroupsLayout() {
    const { data: group } = useAddressBlockGroupSWR()

    useTitle(group?.name)

    return (
        <>
            <Outlet />
        </>
    )
}
