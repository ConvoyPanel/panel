import useAddressPoolSWR from '@/api/admin/addressPools/useAddressPoolSWR'

import Breadcrumbs from '@/components/elements/Breadcrumbs'
import PageContentBlock, {
    PageContentBlockProps,
} from '@/components/elements/PageContentBlock'

interface Props extends PageContentBlockProps {
    title: string
}

const PoolContentBlock = ({ title, children, ...props }: Props) => {
    const { data: pool } = useAddressPoolSWR()

    return (
        <PageContentBlock title={`${title} | ${pool.name}`} {...props}>
            <Breadcrumbs.Generate />
            {children}
        </PageContentBlock>
    )
}

export default PoolContentBlock