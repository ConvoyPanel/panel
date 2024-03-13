import { classNames } from '@/util/helpers'
import styled from '@emotion/styled'
import tw, { TwStyle } from 'twin.macro'

export type FlashMessageType = 'success' | 'info' | 'warning' | 'error'

interface Props {
    title?: string
    children: string
    type?: FlashMessageType
    className?: string
}

const styling = (type?: FlashMessageType): TwStyle | string => {
    switch (type) {
        case 'error':
            return tw`bg-error`
        case 'info':
            return tw`bg-accent-600`
        case 'success':
            return tw`bg-green-600`
        case 'warning':
            return tw`bg-warning-dark`
        default:
            return ''
    }
}

const getBackground = (type?: FlashMessageType): TwStyle | string => {
    switch (type) {
        case 'error':
            return 'bg-red-800'
        case 'info':
            return 'bg-accent-500'
        case 'success':
            return 'bg-green-500'
        case 'warning':
            return 'bg-warning'
        default:
            return ''
    }
}

const Container = styled.div<{ $type?: FlashMessageType }>`
    ${tw`p-2 items-center leading-normal rounded flex w-full text-sm text-white`};
    ${props => styling(props.$type)};
`

const MessageBox = ({ title, children, type, className }: Props) => (
    <Container
        className={classNames('lg:inline-flex', className)}
        $type={type}
        role={'alert'}
    >
        {title && (
            <span
                className={`title flex rounded uppercase px-2 py-1 text-xs font-bold mr-3 leading-none ${getBackground(
                    type
                )}`}
            >
                {title}
            </span>
        )}
        <span className='mr-2 text-left flex-auto'>{children}</span>
    </Container>
)

export default MessageBox
