import styled from '@emotion/styled'
import { Checkbox as MantineCheckbox, CheckboxProps } from '@mantine/core'
import { CheckIcon, MinusIcon } from '@heroicons/react/20/solid'
import { classNames } from '@/util/helpers'
import tw from 'twin.macro'
import { forwardRef } from 'react'

const StyledCheckbox = styled(MantineCheckbox)`
    .mantine-Checkbox-body {
        ${tw`items-center`}
    }

    .mantine-Checkbox-inner {
        ${tw`w-4 h-4`}
    }

    .mantine-Checkbox-input {
        ${tw`w-4 h-4 rounded-[3px] border-accent-500 hover:border-foreground active:bg-accent-200 disabled:bg-accent-100 disabled:border-accent-300`}
    }

    .mantine-Checkbox-input:checked {
        ${tw`border-foreground disabled:bg-accent-300 disabled:border-accent-300`}
        ${({ indeterminate }) => (!indeterminate ? tw`bg-foreground` : null)} ]
    }

    .mantine-Checkbox-label[data-disabled] {
        ${tw`text-accent-300`}
    }

    .mantine-Checkbox-label {
        ${tw`text-foreground`}
    }
`

const Icon = ({ indeterminate, className }: { indeterminate: boolean; className: string }) => {
    return !indeterminate ? (
        <CheckIcon className={classNames('stroke-2 stroke-background text-background', className)} />
    ) : (
        <MinusIcon
            className={classNames(
                'stroke-2',
                false ? 'stroke-accent-300 text-accent-300' : 'stroke-foreground text-foreground', // supposed to switch based on "disabled" state
                className
            )}
        />
    )
}

const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>((props, ref) => {
    return <StyledCheckbox ref={ref} icon={Icon} {...props} />
})

export default Checkbox
