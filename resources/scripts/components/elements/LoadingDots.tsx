import styled from '@emotion/styled'
import { keyframes } from '@emotion/react'
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


        ${({ size = 2 }) => `
            width: ${size}px;
            height: ${size}px;
        `}
    }

    & > span:not(:last-child) {
        margin-right: ${({ size = 2 }) => size / 2}px;
    }

    & > span:nth-of-type(2) {
        animation-delay: 0.2s;
    }

    & > span:nth-of-type(3) {
        animation-delay: 0.4s;
    }
`

interface Props {
    size?: number
}

const LoadingDots = (props: Props) => {
    return <StyledLoadingDots {...props}>
        <span />
        <span />
        <span />
    </StyledLoadingDots>
}

export default LoadingDots