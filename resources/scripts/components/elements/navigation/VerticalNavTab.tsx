import styled from '@emotion/styled'
import { NavLink } from 'react-router-dom'
import tw from 'twin.macro'

const VerticalNavTab = styled(NavLink)`
    ${tw`transition text-sm block text-accent-600`}

    @media (min-width: 960px) {
        ${tw`px-3 py-2 hover:bg-accent-200 hover:text-foreground rounded`}

        &.active {
            ${tw`text-foreground font-medium`}
        }
    }
    @media (max-width: 960px) {
        ${tw`py-6 text-foreground`}
    }
`

export default VerticalNavTab
