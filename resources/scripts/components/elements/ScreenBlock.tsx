import PageContentBlock from '@/components/elements/PageContentBlock'

interface BaseProps {
  title: string
  image?: string
  message: string
  onRetry?: () => void
  onBack?: () => void
}

const ScreenBlock = ({ title, image, message, onBack, onRetry }: BaseProps) => {
  return (
    <PageContentBlock title={title}>
      <div className='flex justify-center'>
        <div className='w-full sm:max-w-md p-12 md:p-20 bg-white dark:bg-primary'>

        </div>
      </div>
    </PageContentBlock>
  )
}

export const NotFound = ({
  title,
  message,
  onBack,
}: Partial<Pick<BaseProps, 'title' | 'message' | 'onBack'>>) => (
  <ScreenBlock
    title={title || '404'}
    message={
      message || "The link is either broken or doesn't exist on the server."
    }
    onBack={onBack}
  />
)

export default ScreenBlock
