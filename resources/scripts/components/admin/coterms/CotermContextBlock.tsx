import useCotermSWR from '@/api/admin/coterms/useCotermSWR'

import PageContentBlock, {
    PageContentBlockProps,
} from '@/components/elements/PageContentBlock'

interface Props extends PageContentBlockProps {
    title: string
}

const CotermContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const { data: coterm } = useCotermSWR()

    return (
        <PageContentBlock title={`${title} | ${coterm.name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default CotermContentBlock