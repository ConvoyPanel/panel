import PageContentBlock, { PageContentBlockProps } from '@/components/elements/PageContentBlock'
import { AdminUserContext } from '@/state/admin/user'
interface Props extends PageContentBlockProps {
    title: string
}

const UserContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const name = AdminUserContext.useStoreState(state => state.user.data!.name)

    return (
        <PageContentBlock title={`${title} | ${name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default UserContentBlock
