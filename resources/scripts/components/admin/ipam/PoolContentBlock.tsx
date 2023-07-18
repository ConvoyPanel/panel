import PageContentBlock, { PageContentBlockProps } from '@/components/elements/PageContentBlock'
import useAddressPoolSWR from '@/api/admin/addressPools/useAddressPoolSWR'

interface Props extends PageContentBlockProps {
    title: string
}

const PoolContentBlock = ({ title, children, ...props }: Props) => {
    const { data: pool } = useAddressPoolSWR()

    return (
        <PageContentBlock title={`${title} | ${pool.name}`} {...props}>
            {children}
        </PageContentBlock>
    )
}

export default PoolContentBlock
