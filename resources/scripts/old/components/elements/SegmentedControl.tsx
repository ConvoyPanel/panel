import styled from '@emotion/styled'
import { SegmentedControl as MantineSegmentedControl } from '@mantine/core'
import tw from 'twin.macro'

const SegmentedControl = styled(MantineSegmentedControl)`
    &.mantine-SegmentedControl-root {
        ${tw`bg-background border border-accent-200 rounded`}
    }

    & .mantine-SegmentedControl-active {
        ${tw`bg-accent-200 shadow-none`}
    }

    & .mantine-SegmentedControl-label {
        ${tw`text-accent-500 hover:text-foreground`}
    }

    & .mantine-SegmentedControl-labelActive {
        ${tw`text-foreground`}
    }
`

export default SegmentedControl
