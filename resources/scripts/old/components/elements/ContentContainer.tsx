import styled from '@emotion/styled'
import tw from 'twin.macro'

interface Props {
    padding?: boolean
}

const ContentContainer = styled.div<Props>`
    ${tw`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`}

    ${({ padding }) => (padding ? tw`py-6` : '')}
`

export default ContentContainer
