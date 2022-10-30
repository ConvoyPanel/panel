import PageContentBlock from '@/components/elements/PageContentBlock'
import { Button } from '@mantine/core'
import { ComponentType } from 'react'

export interface IconProps {
  className?: string
}

interface BaseProps {
  title: string
  icon?: ComponentType<IconProps>
  message: string
  full?: boolean
  onRetry?: () => void
  onBack?: () => void
}

interface PropsWithRetry extends BaseProps {
  onRetry?: () => void
  onBack?: never
}

interface PropsWithBack extends BaseProps {
  onBack?: () => void
  onRetry?: never
}

export type ScreenBlockProps = PropsWithBack | PropsWithRetry

const ScreenBlock = ({
  title,
  icon: Icon,
  message,
  onBack,
  onRetry,
  full,
}: ScreenBlockProps) => {
  return (
    <PageContentBlock
      className={`${full && 'grid place-items-center min-h-screen -mt-10'}`}
      title={title}
    >
      <div className='w-full sm:max-w-md p-12 md:p-20 bg-white dark:bg-primary rounded-md shadow-md text-center'>
        {Icon && <Icon className='' />}

        <h2 className='text-stone-900 dark:text-white font-bold text-4xl'>{title}</h2>
        <p className='description-small mt-3'>{message}</p>
        {(onBack || onRetry) && (
          <div className='flex justify-center mt-3'>
            <Button
              onClick={() => (onRetry ? onRetry() : onBack ? onBack() : null)}
            >
              {onBack ? 'Go Back' : 'Retry'}
            </Button>
          </div>
        )}
      </div>
    </PageContentBlock>
  )
}

export const NotFound = ({
  title,
  message,
  onBack,
  full,
}: Partial<Pick<BaseProps, 'title' | 'message' | 'onBack' | 'full'>>) => (
  <ScreenBlock
    title={title || '404'}
    message={
      message || "The link is either broken or doesn't exist on the server."
    }
    full={full}
    onBack={onBack}
  />
)

export default ScreenBlock
