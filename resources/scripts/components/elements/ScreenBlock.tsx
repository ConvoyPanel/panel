import PageContentBlock from '@/components/elements/PageContentBlock';

interface BaseProps {
    title: string;
    image?: string;
    message: string;
    onRetry?: () => void;
    onBack?: () => void;
}

const ScreenBlock = ({ title, image, message, onBack, onRetry}: BaseProps) => {
    return <PageContentBlock title={title} >
        <div className='flex justify-center'>
            <div className='w-full sm:max-w-md p-12 md:p-20'>

            </div>
        </div>
    </PageContentBlock>
}

export default ScreenBlock