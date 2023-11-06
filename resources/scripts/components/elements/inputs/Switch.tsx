import styled from '@emotion/styled'
import {
    Switch as MantineSwitch,
    SwitchProps as MantineSwitchProps,
} from '@mantine/core'
import tw from 'twin.macro'

export interface SwitchProps extends MantineSwitchProps {}

const Switch = styled(MantineSwitch)`
    & .mantine-Switch-track {
        ${tw`h-6 w-11 rounded-xl bg-accent-200 transition-colors border-none`}
    }

    & input:checked + .mantine-Switch-track {
        ${tw`bg-success`}
    }

    & .mantine-Switch-thumb {
        ${tw`h-5 w-5 border-none transition-all bg-background`}
    }

    & input:checked + * > .mantine-Switch-thumb {
        left: calc(100% - 1.4rem);
    }

    & .mantine-Switch-label {
        ${tw`text-foreground`}
    }

    & .mantine-Switch-labelWrapper {
        ${tw`self-center`}
    }
`

export default Switch
