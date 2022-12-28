import styled from '@emotion/styled'
import tw from 'twin.macro'

const Row = styled.div`
  ${tw`grid p-4 items-stretch justify-center bg-background border border-accent-200 rounded`}

  & > div {
    ${tw`pt-4 md:pt-0 mt-4 md:mt-0 border-t md:border-t-0 border-accent-200`}
  }

  & > div:is(:first-of-type) {
    ${tw`border-t-0 pt-0 mt-0`}
  }
`

const Group = styled.div`
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

const Display = {
    Row,
    Group
}

export default Display
