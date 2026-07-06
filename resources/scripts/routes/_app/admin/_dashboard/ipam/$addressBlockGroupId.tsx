import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import useAddressBlockGroup, {
    preloadAddressBlockGroup,
} from '@/api/admin/addressBlockGroups/use-address-block-group.ts'

export const Route = createFileRoute(
    '/_app/admin/_dashboard/ipam/$addressBlockGroupId'
)({
    loader: ({ params: { addressBlockGroupId } }) =>
        preloadAddressBlockGroup(Number(addressBlockGroupId)).catch(
            processAxiosError
        ),
    component: BlockGroupsLayout,
    staticData: {
        title: 'IP Block Group',
    }
})

function BlockGroupsLayout() {
    const { data: group } = useAddressBlockGroup()

    useTitle(group?.name)

    return (
        <>
            <Outlet />
        </>
    )
}
