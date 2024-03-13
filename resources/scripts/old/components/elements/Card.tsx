import styled from '@emotion/styled'
import tw from 'twin.macro'

interface Props {
    overridePadding?: boolean
}

const Card = styled.div<Props>`
    ${tw`border border-[#eaeaea] dark:border-[#333333] bg-white dark:bg-black rounded shadow-light dark:shadow-none`}

    ${props => !props.overridePadding && tw`p-6`}
`

export default Card
