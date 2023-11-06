import { keyframes } from '@emotion/react'
import styled from '@emotion/styled'
import tw from 'twin.macro'

const loadingDotsAnimation = keyframes`
0% {
    opacity: .2
}
20% {
    opacity: 1
}
to {
    opacity: .2
}
`

const StyledLoadingDots = styled.span<Props>`
    ${tw`flex`}

    & > span {
        ${tw`rounded-full w-0.5 h-0.5 bg-accent-600 transition-colors`}

        animation-name: ${loadingDotsAnimation};
        animation-iteration-count: infinite;
        animation-duration: 1.4s;
        animation-fill-mode: both;

        ${({ size = 2 }) => `
            width: ${size}px;
            height: ${size}px;
        `}
    }

    & > span:not(:last-child) {
        margin-right: ${({ size = 2 }) => size / 2}px;
    }
`

interface Props {
    size?: number
}

const LoadingDots = (props: Props) => {
    return (
        <StyledLoadingDots {...props}>
            <span />
            <span style={{ animationDelay: '0.2s' }} />
            <span style={{ animationDelay: '0.4s' }} />
        </StyledLoadingDots>
    )
}

export default LoadingDots
