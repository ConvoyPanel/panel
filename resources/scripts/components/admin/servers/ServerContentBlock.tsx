import { AdminServerContext } from '@/state/admin/server'

import PageContentBlock, {
    PageContentBlockProps,
} from '@/components/elements/PageContentBlock'

interface Props extends PageContentBlockProps {
    title: string
}

const ServerContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const name = AdminServerContext.useStoreState(
        state => state.server.data!.name
    )

    return (
        <PageContentBlock title={`${title} | ${name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default ServerContentBlock