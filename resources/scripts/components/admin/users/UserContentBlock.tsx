import useUserSWR from '@/api/admin/users/useUserSWR'

import PageContentBlock, {
    PageContentBlockProps,
} from '@/components/elements/PageContentBlock'

interface Props extends PageContentBlockProps {
    title: string
}

const UserContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const { data: user } = useUserSWR()

    return (
        <PageContentBlock title={`${title} | ${user.name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default UserContentBlock