import { NodeContext } from '@/state/admin/node'

import PageContentBlock, {
    PageContentBlockProps,
} from '@/components/elements/PageContentBlock'

interface Props extends PageContentBlockProps {
    title: string
}

const NodeContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const name = NodeContext.useStoreState(state => state.node.data!.name)

    return (
        <PageContentBlock title={`${title} | ${name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default NodeContentBlock
