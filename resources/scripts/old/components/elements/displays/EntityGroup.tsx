import styled from '@emotion/styled'
import tw from 'twin.macro'

const EntityGroup = styled.div`
    ${tw`flex flex-col`}

    & > div {
        ${tw`rounded-none`}
    }

    & > div:is(:first-of-type) {
        ${tw`rounded-t`}
    }

    & > div:not(:first-of-type) {
        ${tw`-mt-[1px]`}
    }

    & > div:is(:last-child) {
        ${tw`rounded-b`}
    }
`

export default EntityGroup
