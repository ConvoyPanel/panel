import styled from '@emotion/styled'
import { Radio as MantineRadio, RadioGroupProps, RadioProps } from '@mantine/core'
import tw from 'twin.macro'

interface Radio extends React.FC<RadioProps> {
    Group: React.FC<RadioGroupProps>
}

const StyledRadio = styled(MantineRadio)`
    & .mantine-Radio-radio {
        ${tw`h-4 w-4 bg-transparent border-accent-500`}
    }

    & .mantine-Radio-inner {
        ${tw`self-center`}
    }

    & .mantine-Radio-body {
        ${tw`items-center`}
    }

    & .mantine-Radio-radio:checked {
        ${tw`border-foreground`}
    }

    & .mantine-Radio-label {
        ${tw`pl-2 text-foreground`}
    }

    & .mantine-Radio-icon {
        ${tw`text-foreground`}
    }
`

const Radio: Radio = ({ ...props }) => <StyledRadio {...props} />

Radio.Group = ({ ...props }) => <MantineRadio.Group {...props} />

export default Radio