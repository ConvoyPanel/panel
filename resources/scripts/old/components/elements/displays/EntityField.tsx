import { classNames } from '@/util/helpers'
import { ReactNode } from 'react'

interface Props {
    title?: string
    description?: string
    width?: string

    actions?: ReactNode
    isFirst?: boolean
    isLast?: boolean
}

const EntityField = ({
    title,
    description,
    width,
    actions,
    isFirst,
    isLast,
}: Props) => {
    const style = {
        flex: width ? `0 0 ${width}` : undefined,
    }

    return (
        <div
            className={classNames(
                'flex max-sm:pt-4 max-sm:border-t border-accent-200',
                !width ? 'flex-1' : null
            )}
            style={style}
        >
            <div className='flex-grow'>
                {title && (
                    <p className='text-sm text-foreground font-semibold truncate'>
                        {title}
                    </p>
                )}
                {description && (
                    <p className='text-sm text-foreground truncate'>
                        {description}
                    </p>
                )}
            </div>
            {isFirst || isLast ? (
                <div
                    className={classNames(
                        'items-center pl-4',
                        !isFirst && isLast ? 'hidden sm:flex' : null,
                        !isLast && isFirst ? 'flex sm:hidden' : null
                    )}
                >
                    {actions}
                </div>
            ) : null}
        </div>
    )
}

export default EntityField
