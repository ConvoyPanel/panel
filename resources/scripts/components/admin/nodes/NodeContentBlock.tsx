import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import PageContentBlock, {
    PageContentBlockProps,
} from '@/components/elements/PageContentBlock'

interface Props extends PageContentBlockProps {
    title: string
}

const NodeContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const { data: node } = useNodeSWR()

    return (
        <PageContentBlock title={`${title} | ${node.name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default NodeContentBlock